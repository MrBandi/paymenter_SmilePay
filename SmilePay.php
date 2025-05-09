<?php

namespace Paymenter\Extensions\Gateways\SmilePay;

use App\Classes\Extension\Gateway;
use App\Helpers\ExtensionHelper;
use App\Models\Gateway as ModelsGateway;
use App\Models\Invoice;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

class SmilePay extends Gateway
{
    // 除錯日誌表名
    private const DEBUG_LOG_TABLE = 'smilepay_debug_logs';

    public function boot()
    {
        require __DIR__ . '/routes.php';
        // Register webhook route
        View::addNamespace('gateways.smilepay', __DIR__ . '/resources/views');
        
        // Create the smilepay_payments table if it doesn't exist
        $this->createPaymentsTableIfNotExists();
        
        // Create debug log table if debug mode is enabled
        if ($this->isDebugMode()) {
            $this->createDebugLogTableIfNotExists();
        }
    }

    private function createPaymentsTableIfNotExists()
    {
        if (!Schema::hasTable('smilepay_payments')) {
            try {
                Schema::create('smilepay_payments', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('invoice_id');
                    $table->string('smilepay_no');
                    $table->text('payment_info');
                    $table->boolean('paid')->default(false);
                    $table->timestamps();
                    
                    $table->index('invoice_id');
                    $table->index('smilepay_no');
                });
                
                $this->logDebug('SmilePay payments table created successfully');
            } catch (\Exception $e) {
                $this->logDebug('Failed to create SmilePay payments table: ' . $e->getMessage(), 'error');
            }
        }
    }
    
    /**
     * 創建除錯日誌資料表
     */
    private function createDebugLogTableIfNotExists()
    {
        if (!Schema::hasTable(self::DEBUG_LOG_TABLE)) {
            try {
                Schema::create(self::DEBUG_LOG_TABLE, function (Blueprint $table) {
                    $table->id();
                    $table->string('level')->default('info');
                    $table->text('message');
                    $table->json('context')->nullable();
                    $table->unsignedBigInteger('invoice_id')->nullable();
                    $table->timestamps();
                    
                    $table->index('invoice_id');
                    $table->index('level');
                    $table->index('created_at');
                });
                
                Log::info('SmilePay debug log table created successfully');
            } catch (\Exception $e) {
                Log::error('Failed to create SmilePay debug log table: ' . $e->getMessage());
            }
        }
    }

    public function getConfig($values = [])
    {
        return [
            [
                'name' => 'smilepay_dcvc',
                'label' => 'SmilePay Merchant ID (Dcvc)',
                'placeholder' => '請輸入您的商家代號',
                'type' => 'text',
                'description' => '請至商家後台確認商家代號',
                'required' => true,
            ],
            [
                'name' => 'smilepay_rvg2c',
                'label' => 'SmilePay Parameter Code (Rvg2c)',
                'placeholder' => '請輸入您的參數碼',
                'type' => 'text',
                'description' => '請至商家後台確認參數碼',
                'required' => true,
            ],
            [
                'name' => 'smilepay_verify_key',
                'label' => 'SmilePay Verify Key',
                'placeholder' => '請輸入您的檢查碼',
                'type' => 'text',
                'description' => '請至商家後台確認檢查碼',
                'required' => true,
            ],
            [
                'name' => 'smilepay_merchant_param',
                'label' => 'SmilePay 商家驗證參數',
                'placeholder' => '請輸入您的商家驗證參數',
                'type' => 'text',
                'description' => '用於驗證回調數據的4位數驗證參數',
                'required' => false,
            ],
            [
                'name' => 'smilepay_debug_mode',
                'label' => '啟用除錯模式',
                'type' => 'checkbox',
                'description' => '啟用此選項將記錄詳細的除錯信息並顯示更多錯誤訊息',
                'required' => false,
            ],
            [
                'name' => 'smilepay_test_mode',
                'label' => '測試模式',
                'type' => 'checkbox',
                'description' => '啟用此選項將使用 SmilePay 測試環境而非正式環境',
                'required' => false,
            ],
        ];
    }

    /**
     * 檢查是否啟用除錯模式
     */
    private function isDebugMode()
    {
        return (bool) $this->config('smilepay_debug_mode');
    }
    
    /**
     * 檢查是否啟用測試模式
     */
    private function isTestMode()
    {
        return (bool) $this->config('smilepay_test_mode');
    }
    
    /**
     * 記錄除錯信息
     */
    private function logDebug($message, $level = 'info', $context = [], $invoiceId = null)
    {
        // 記錄到 Laravel 日誌
        Log::$level($message, $context);
        
        // 如果除錯模式啟用，同時記錄到資料庫
        if ($this->isDebugMode() && Schema::hasTable(self::DEBUG_LOG_TABLE)) {
            try {
                DB::table(self::DEBUG_LOG_TABLE)->insert([
                    'level' => $level,
                    'message' => $message,
                    'context' => json_encode($context),
                    'invoice_id' => $invoiceId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to write to SmilePay debug log: ' . $e->getMessage());
            }
        }
    }

    private function request($parameters = [], $invoiceId = null)
    {
        // 基本 API URL
        $apiUrl = $this->isTestMode() 
            ? 'https://ssl.smse.com.tw/api/SPPayment.asp'  // 測試環境 URL (速買配未給, 所以都使用正式環境)
            : 'https://ssl.smse.com.tw/api/SPPayment.asp';  // 正式環境 URL
        
        // 記錄請求參數
        $this->logDebug('Sending request to SmilePay', 'info', [
            'parameters' => $this->maskSensitiveData($parameters),
            'apiUrl' => $apiUrl
        ], $invoiceId);
        
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($apiUrl, $parameters);
            
            // 記錄響應
            $this->logDebug('Received response from SmilePay', 'info', [
                'status' => $response->status(),
                'body' => $response->body()
            ], $invoiceId);
            
            return $response;
        } catch (\Exception $e) {
            $this->logDebug('SmilePay API request failed', 'error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], $invoiceId);
            
            throw $e;
        }
    }
    
    /**
     * 遮蔽敏感數據
     */
    private function maskSensitiveData($data)
    {
        $masked = $data;
        
        // 遮蔽敏感字段
        if (isset($masked['Verify_key'])) {
            $masked['Verify_key'] = '******';
        }
        
        return $masked;
    }

    /**
     * 主要付款處理
     */
    public function pay($invoice, $total)
    {
        try {
            $this->logDebug('SmilePay payment process started', 'info', [
                'invoice_id' => $invoice->id,
                'total' => $total,
                'request_method' => request()->method(),
                'request_all' => request()->all(),
            ], $invoice->id);
            
            // 檢查是否是直接創建付款方式請求
            if (request()->has('create_payment') && request()->has('payment_method')) {
                $paymentMethod = request()->input('payment_method');
                
                try {
                    // 創建付款
                    $paymentInfo = $this->createPayment($invoice, $paymentMethod);
                    
                    // 顯示付款資訊
                    return view('gateways.smilepay::pay', [
                        'invoice' => $invoice, 
                        'total' => $total,
                        'paymentInfo' => $paymentInfo,
                        'debugMode' => $this->isDebugMode()
                    ]);
                } catch (\Exception $e) {
                    // 顯示錯誤
                    return view('gateways.smilepay::error', [
                        'invoice' => $invoice,
                        'error' => $e->getMessage(),
                        'debugMode' => $this->isDebugMode(),
                        'debugInfo' => $this->isDebugMode() ? [
                            'message' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString()
                        ] : null
                    ]);
                }
            }
            
            // 檢查是否已有付款資訊
            $paymentInfo = $this->getExistingPaymentInfo($invoice->id);
            
            if ($paymentInfo) {
                $this->logDebug('Using existing payment information', 'info', [
                    'paymentInfo' => $paymentInfo
                ], $invoice->id);
                
                // 顯示現有的付款資訊
                return view('gateways.smilepay::pay', [
                    'invoice' => $invoice, 
                    'total' => $total,
                    'paymentInfo' => $paymentInfo,
                    'debugMode' => $this->isDebugMode()
                ]);
            }
            
            $this->logDebug('Showing payment method selection', 'info', [
                'current_url' => url()->current()
            ], $invoice->id);
            
            // 讓用戶選擇付款方式
            return view('gateways.smilepay::select_payment', [
                'invoice' => $invoice, 
                'total' => $total,
                'debugMode' => $this->isDebugMode(),
                'invoice_id' => $invoice->id,
                'full_url' => url()->full(),
                'current_url' => url()->current()
            ]);
        } catch (\Exception $e) {
            $this->logDebug('Unexpected error in pay method', 'error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], $invoice->id ?? null);
            
            // 顯示錯誤
            return view('gateways.smilepay::error', [
                'invoice' => $invoice,
                'error' => '處理付款時發生意外錯誤',
                'debugMode' => $this->isDebugMode(),
                'debugInfo' => $this->isDebugMode() ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ] : null
            ]);
        }
    }
    
    /**
     * 根據付款方式創建付款
     */
    public function createPayment($invoice, $paymentMethod)
    {
        $this->logDebug('Creating new payment', 'info', [
            'invoice_id' => $invoice->id,
            'method' => $paymentMethod
        ], $invoice->id);
        
        // 檢查已有付款信息
        $existingInfo = $this->getExistingPaymentInfo($invoice->id);
        if ($existingInfo) {
            $this->logDebug('Payment info already exists', 'info', [
                'existingInfo' => $existingInfo
            ], $invoice->id);
            return $existingInfo;
        }
        
        // 驗證配置
        $dcvc = $this->config('smilepay_dcvc');
        $rvg2c = $this->config('smilepay_rvg2c');
        $verifyKey = $this->config('smilepay_verify_key');
        
        if (empty($dcvc) || empty($rvg2c) || empty($verifyKey)) {
            $this->logDebug('SmilePay configuration missing', 'error', [
                'dcvc' => $dcvc ? 'set' : 'not set',
                'rvg2c' => $rvg2c ? 'set' : 'not set',
                'verify_key' => $verifyKey ? 'set' : 'not set'
            ], $invoice->id);
            
            throw new \Exception('SmilePay 配置不完整，請檢查商家代號、參數碼和檢查碼設定');
        }
        
        $parameters = [
            'Dcvc' => $dcvc,
            'Rvg2c' => $rvg2c,
            'Verify_key' => $verifyKey,
            'Data_id' => $invoice->id,
            'Od_sob' => 'Invoice #' . $invoice->id,
            'Amount' => $invoice->total,
            'Pay_zg' => $paymentMethod, // 2 for ATM, 3 for CVS Code, 4 for 7-11 ibon, 6 for FamiPort
            'Roturl' => route('extensions.gateways.smilepay.webhook'),
            'Deadline_date' => now()->addDays(7)->format('Y/m/d'),
            'Pur_name' => $invoice->user->name ?? '',
            'Tel_number' => $invoice->user->phone ?? '',
            'Mobile_number' => $invoice->user->mobile ?? '',
            'Email' => $invoice->user->email ?? '',
        ];
        
        // 根據付款方式增加特定參數
        if (in_array($paymentMethod, [4, 6])) { // 7-11 ibon 或 FamiPort
            // 挑戰與全家的繳費期限設定
            $parameters['Deadline_date'] = now()->addDays(5)->format('Y/m/d'); // 最多 7 天，預設 5 天
            $parameters['Deadline_time'] = '23:59:59'; // 到期時間
        }
        
        try {
            $response = $this->request($parameters, $invoice->id);
            
            // 嘗試解析 XML 回應
            $xml = null;
            $xmlError = null;
            
            try {
                $xmlString = $response->body();
                $this->logDebug('XML response', 'info', [
                    'response' => $xmlString
                ], $invoice->id);
                
                $xml = simplexml_load_string($xmlString);
                
                if ($xml === false) {
                    $xmlError = libxml_get_errors();
                    libxml_clear_errors();
                    throw new \Exception('XML 解析錯誤: ' . json_encode($xmlError));
                }
            } catch (\Exception $e) {
                $this->logDebug('XML parse error', 'error', [
                    'body' => $response->body(),
                    'error' => $e->getMessage()
                ], $invoice->id);
                
                throw new \Exception('SmilePay 回應格式錯誤: ' . $e->getMessage());
            }
            
            // 檢查狀態碼
            if ((string)$xml->Status != '1') {
                $this->logDebug('SmilePay returned error', 'error', [
                    'status' => (string)$xml->Status,
                    'desc' => (string)$xml->Desc
                ], $invoice->id);
                
                throw new \Exception('建立付款失敗: ' . (string)$xml->Desc);
            }
            
            // Convert XML to payment info array
            $paymentInfo = $this->convertXmlToPaymentInfo($xml);
            
            // Store payment information
            $this->storePaymentInfo($invoice->id, $paymentInfo);
            
            $this->logDebug('Payment created successfully', 'info', [
                'paymentInfo' => $paymentInfo
            ], $invoice->id);
            
            return $paymentInfo;
        } catch (\Exception $e) {
            $this->logDebug('Error in createPayment', 'error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], $invoice->id);
            
            throw $e;
        }
    }
    
    /**
     * 獲取既有的付款資訊
     */
    private function getExistingPaymentInfo($invoiceId)
    {
        try {
            $payment = DB::table('smilepay_payments')
                ->where('invoice_id', $invoiceId)
                ->first();
                
            if (!$payment) {
                $this->logDebug('No existing payment found', 'info', [], $invoiceId);
                return null;
            }
            
            $paymentInfo = json_decode($payment->payment_info, true);
            
            $this->logDebug('Existing payment found', 'info', [
                'paymentInfo' => $paymentInfo
            ], $invoiceId);
            
            return $paymentInfo;
        } catch (\Exception $e) {
            $this->logDebug('Error getting existing payment info', 'error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], $invoiceId);
            
            return null;
        }
    }
    
    /**
     * 保存付款資訊
     */
    private function storePaymentInfo($invoiceId, $paymentInfo)
    {
        try {
            // Insert or update the payment info
            DB::table('smilepay_payments')->updateOrInsert(
                ['invoice_id' => $invoiceId],
                [
                    'smilepay_no' => $paymentInfo['smilepayNo'],
                    'payment_info' => json_encode($paymentInfo),
                    'updated_at' => now(),
                    'created_at' => DB::raw('CASE WHEN created_at IS NULL THEN NOW() ELSE created_at END'),
                ]
            );
            
            $this->logDebug('Payment info stored successfully', 'info', [], $invoiceId);
        } catch (\Exception $e) {
            $this->logDebug('Error storing payment info', 'error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], $invoiceId);
            
            throw $e;
        }
    }
    
    /**
     * 將 XML 轉換為付款資訊陣列
     */
    private function convertXmlToPaymentInfo($xml)
    {
        $paymentInfo = [
            'status' => (string)$xml->Status,
            'description' => (string)$xml->Desc,
            'smilepayNo' => (string)$xml->SmilePayNO,
            'amount' => (string)$xml->Amount,
        ];
        
        // Add payment method specific information
        if (isset($xml->AtmBankNo)) {
            $paymentInfo['atmBankNo'] = (string)$xml->AtmBankNo;
            $paymentInfo['atmNo'] = (string)$xml->AtmNo;
            $paymentInfo['paymentType'] = 'atm';
        } elseif (isset($xml->Barcode1)) {
            $paymentInfo['barcode1'] = (string)$xml->Barcode1;
            $paymentInfo['barcode2'] = (string)$xml->Barcode2;
            $paymentInfo['barcode3'] = (string)$xml->Barcode3;
            $paymentInfo['paymentType'] = 'cvs';
        } elseif (isset($xml->IbonNo)) {
            $paymentInfo['ibonNo'] = (string)$xml->IbonNo;
            $paymentInfo['paymentType'] = 'ibon';
        } elseif (isset($xml->FamiNO)) {
            $paymentInfo['famiNo'] = (string)$xml->FamiNO;
            $paymentInfo['paymentType'] = 'fami';
        }
        
        // 處理 API 回傳的繳費期限
        if (isset($xml->PayEndDate)) {
            $paymentInfo['payEndDate'] = (string)$xml->PayEndDate;
        }
        
        // 記錄所使用的付款方式代碼
        $methodMap = [
            'atm' => 2,
            'cvs' => 3,
            'ibon' => 4,
            'fami' => 6
        ];
        
        if (isset($paymentInfo['paymentType']) && isset($methodMap[$paymentInfo['paymentType']])) {
            $paymentInfo['paymentMethod'] = $methodMap[$paymentInfo['paymentType']];
        }
        
        return $paymentInfo;
    }
    
    /**
     * 處理 Webhook
     */
    public function webhook(Request $request)
    {
        // Log the webhook data
        $this->logDebug('SmilePay webhook received', 'info', $request->all());
        
        // Process the payment notification
        $data = $request->all();
        
        // 驗證回調
        if (!$this->verifyWebhook($request)) {
            $this->logDebug('Invalid webhook signature', 'error', $data);
            return response('<Roturlstatus>RL_FAIL</Roturlstatus>', 200)
                ->header('Content-Type', 'text/html');
        }
        
        // Check if payment was successful
        if (isset($data['Classif']) && isset($data['Data_id'])) {
            $invoiceId = $data['Data_id'];
            $amount = $data['Amount'];
            $transactionId = $data['Smseid'];
            
            $this->logDebug('Processing webhook payment', 'info', [
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'transaction_id' => $transactionId
            ], $invoiceId);
            
            try {
                // Mark the payment as paid in our database
                DB::table('smilepay_payments')
                    ->where('invoice_id', $invoiceId)
                    ->update(['paid' => true]);
                
                // Add payment to the system
                ExtensionHelper::addPayment($invoiceId, 'SmilePay', $amount, 0, $transactionId);
                
                $this->logDebug('Payment successfully recorded', 'info', [], $invoiceId);
                
                // Return success response
                return response('<Roturlstatus>RL_OK</Roturlstatus>', 200)
                    ->header('Content-Type', 'text/html');
            } catch (\Exception $e) {
                $this->logDebug('Error processing webhook payment', 'error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], $invoiceId);
                
                return response('<Roturlstatus>RL_FAIL</Roturlstatus>', 200)
                    ->header('Content-Type', 'text/html');
            }
        }
        
        $this->logDebug('Invalid webhook data', 'error', $data);
        
        return response('<Roturlstatus>RL_FAIL</Roturlstatus>', 200)
            ->header('Content-Type', 'text/html');
    }
    
    /**
     * 驗證 Webhook
     */
    private function verifyWebhook(Request $request)
    {
        $data = $request->all();
        
        // 如果測試模式啟用，跳過驗證
        if ($this->isTestMode()) {
            $this->logDebug('Webhook verification skipped in test mode', 'info', $data);
            return true;
        }
        
        // Implement SmilePay verification
        if (isset($data['Mid_smilepay'])) {
            $merchantParam = $this->config('smilepay_merchant_param');
            if (!$merchantParam) {
                $this->logDebug('Merchant verification parameter not set, skipping verification', 'info', $data);
                return true; // No verification parameter set, skip verification
            }
            
            // Calculate verification code according to SmilePay documentation
            $calculatedMid = $this->calculateVerificationCode($data, $merchantParam);
            
            $result = $calculatedMid == $data['Mid_smilepay'];
            
            $this->logDebug('Webhook verification result', 'info', [
                'calculated' => $calculatedMid,
                'received' => $data['Mid_smilepay'],
                'result' => $result ? 'valid' : 'invalid'
            ]);
            
            return $result;
        }
        
        $this->logDebug('Webhook verification failed, Mid_smilepay not found', 'error', $data);
        
        return false;
    }
    
    /**
     * 計算驗證碼
     */
    private function calculateVerificationCode($data, $merchantParam)
    {
        // A = Merchant verification parameter (4 digits, pad with zeros if needed)
        $a = str_pad($merchantParam, 4, '0', STR_PAD_LEFT);
        
        // B = Payment amount (8 digits, pad with zeros if needed)
        $b = str_pad($data['Amount'], 8, '0', STR_PAD_LEFT);
        
        // C = Last 4 digits of Smseid (replace non-numeric with 9)
        $smseid = $data['Smseid'];
        $lastFour = substr($smseid, -4);
        $c = '';
        for ($i = 0; $i < 4; $i++) {
            $char = $i < strlen($lastFour) ? $lastFour[$i] : '';
            $c .= is_numeric($char) ? $char : '9';
        }
        
        // D = A & B & C
        $d = $a . $b . $c;
        
        // E = Sum of even position digits in D * 3
        $e = 0;
        for ($i = 0; $i < strlen($d); $i++) {
            if (($i + 1) % 2 == 0) { // Even position (1-indexed)
                $e += intval($d[$i]);
            }
        }
        $e *= 3;
        
        // F = Sum of odd position digits in D * 9
        $f = 0;
        for ($i = 0; $i < strlen($d); $i++) {
            if (($i + 1) % 2 == 1) { // Odd position (1-indexed)
                $f += intval($d[$i]);
            }
        }
        $f *= 9;
        
        // Mid_smilepay = E + F
        return $e + $f;
    }
    
    /**
     * 特殊方法：直接處理付款創建
     * 這是為了跳過 Livewire 處理
     */
    public function directCreatePayment($invoiceId, $paymentMethod)
    {
        try {
            $invoice = Invoice::findOrFail($invoiceId);
            
            $paymentInfo = $this->createPayment($invoice, $paymentMethod);
            
            return redirect()->back()->with('success', '付款資訊已建立');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    
    /**
     * 顯示除錯日誌（管理員功能）
     */
    public function showDebugLogs($invoiceId = null)
    {
        if (!$this->isDebugMode()) {
            return redirect()->back()->with('error', '除錯模式未啟用');
        }
        
        $query = DB::table(self::DEBUG_LOG_TABLE)
            ->orderBy('created_at', 'desc');
            
        if ($invoiceId) {
            $query->where('invoice_id', $invoiceId);
        }
        
        $logs = $query->paginate(50);
        
        return view('gateways.smilepay::debug_logs', [
            'logs' => $logs,
            'invoiceId' => $invoiceId
        ]);
    }
    
    /**
     * 清除除錯日誌（管理員功能）
     */
    public function clearDebugLogs($invoiceId = null)
    {
        if (!$this->isDebugMode()) {
            return redirect()->back()->with('error', '除錯模式未啟用');
        }
        
        $query = DB::table(self::DEBUG_LOG_TABLE);
        
        if ($invoiceId) {
            $query->where('invoice_id', $invoiceId);
        }
        
        $query->delete();
        
        return redirect()->back()->with('success', '除錯日誌已清除');
    }
}
