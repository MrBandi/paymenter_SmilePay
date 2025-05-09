<div class="mt-2 text-white">
    <div class="mb-4">
        <h3 class="font-medium mb-2">付款資訊</h3>
    </div>
    
    @if($paymentInfo['paymentType'] == 'atm')
        <div class="mb-4">
            <h4 class="text-secondary mb-2">銀行轉帳 (ATM)</h4>
            
            <div class="grid grid-cols-2 gap-2">
                <div class="text-gray-400">銀行代號:</div>
                <div>{{ $paymentInfo['atmBankNo'] }}</div>
                
                <div class="text-gray-400">虛擬帳號:</div>
                <div>{{ $paymentInfo['atmNo'] }}</div>
                
                @if(isset($paymentInfo['payEndDate']))
                    <div class="text-gray-400">繳費期限:</div>
                    <div>{{ $paymentInfo['payEndDate'] }}</div>
                @endif
                
                <div class="text-gray-400">金額:</div>
                <div>NT$ {{ number_format($total, 2) }}</div>
            </div>
        </div>
    @elseif($paymentInfo['paymentType'] == 'cvs')
        <div class="mb-4">
            <h4 class="text-secondary mb-2">超商代碼</h4>
            
            <div class="grid grid-cols-2 gap-2">
                <div class="text-gray-400">條碼 1:</div>
                <div>{{ $paymentInfo['barcode1'] }}</div>
                
                <div class="text-gray-400">條碼 2:</div>
                <div>{{ $paymentInfo['barcode2'] }}</div>
                
                <div class="text-gray-400">條碼 3:</div>
                <div>{{ $paymentInfo['barcode3'] }}</div>
                
                @if(isset($paymentInfo['payEndDate']))
                    <div class="text-gray-400">繳費期限:</div>
                    <div>{{ $paymentInfo['payEndDate'] }}</div>
                @endif
                
                <div class="text-gray-400">金額:</div>
                <div>NT$ {{ number_format($total, 2) }}</div>
            </div>
        </div>
    @elseif($paymentInfo['paymentType'] == 'ibon')
        <div class="mb-4">
            <h4 class="text-secondary mb-2">7-11 ibon</h4>
            
            <div class="grid grid-cols-2 gap-2">
                <div class="text-gray-400">繳費代碼:</div>
                <div>{{ $paymentInfo['ibonNo'] }}</div>
                
                @if(isset($paymentInfo['payEndDate']))
                    <div class="text-gray-400">繳費期限:</div>
                    <div>{{ $paymentInfo['payEndDate'] }}</div>
                @endif
                
                <div class="text-gray-400">金額:</div>
                <div>NT$ {{ number_format($total, 2) }}</div>
            </div>
            
            <div class="mt-2 text-xs text-gray-400">
                <p class="font-medium text-white">使用方式：</p>
                <ol class="list-decimal pl-5 space-y-1 mt-1">
                    <li>至任一 7-11 超商</li>
                    <li>於 ibon 觸控螢幕選擇「繳費」</li>
                    <li>選擇「代碼繳費」</li>
                    <li>輸入繳費代碼 {{ $paymentInfo['ibonNo'] }}</li>
                    <li>確認金額後，到櫃台繳費</li>
                </ol>
            </div>
        </div>
    @elseif($paymentInfo['paymentType'] == 'fami')
        <div class="mb-4">
            <h4 class="text-secondary mb-2">全家 FamiPort</h4>
            
            <div class="grid grid-cols-2 gap-2">
                <div class="text-gray-400">繳費代碼:</div>
                <div>{{ $paymentInfo['famiNo'] }}</div>
                
                @if(isset($paymentInfo['payEndDate']))
                    <div class="text-gray-400">繳費期限:</div>
                    <div>{{ $paymentInfo['payEndDate'] }}</div>
                @endif
                
                <div class="text-gray-400">金額:</div>
                <div>NT$ {{ number_format($total, 2) }}</div>
            </div>
            
            <div class="mt-2 text-xs text-gray-400">
                <p class="font-medium text-white">使用方式：</p>
                <ol class="list-decimal pl-5 space-y-1 mt-1">
                    <li>至任一全家便利商店</li>
                    <li>於 FamiPort 觸控螢幕選擇「繳費」</li>
                    <li>選擇「代碼繳費」</li>
                    <li>輸入繳費代碼 {{ $paymentInfo['famiNo'] }}</li>
                    <li>確認金額後，到櫃台繳費</li>
                </ol>
            </div>
        </div>
    @endif
    
    <div class="mt-4 text-sm text-gray-400">
        <p>請在繳費期限前完成付款。付款完成後，系統將自動處理您的訂單。</p>
        <p class="mt-1">如需使用其他付款方式，請選擇下方按鈕返回選擇頁面。</p>
    </div>
    
    <div class="mt-4">
        <a href="javascript:history.back()" class="bg-gray-700 text-white hover:bg-gray-600 py-2 px-4 rounded-md inline-block text-sm transition-colors duration-300">
            &larr; 返回選擇其他付款方式
        </a>
    </div>
    
    @if(isset($debugMode) && $debugMode)
    <div class="mt-4 bg-gray-800 p-4 rounded text-xs">
        <h4 class="font-medium mb-2 text-yellow-500">除錯信息</h4>
        <div>
            <span class="text-gray-400">訂單 ID:</span>
            <span class="text-white">{{ $invoice->id }}</span>
        </div>
        <div>
            <span class="text-gray-400">付款類型:</span>
            <span class="text-white">{{ $paymentInfo['paymentType'] }}</span>
        </div>
        <div>
            <span class="text-gray-400">SmilePay 訂單號:</span>
            <span class="text-white">{{ $paymentInfo['smilepayNo'] }}</span>
        </div>
        <div class="mt-2">
            <span class="text-gray-400">完整付款信息:</span>
            <pre class="mt-1 text-green-400 whitespace-pre-wrap">{{ json_encode($paymentInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>
    @endif
</div>
