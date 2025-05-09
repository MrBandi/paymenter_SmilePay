<div class="mt-2 text-white">
    <div class="mb-4">
        <h3 class="font-medium mb-2">選擇付款方式</h3>
    </div>
    
    <div class="grid grid-cols-1 gap-4 mb-4">
        <a href="{{ route('extensions.gateways.smilepay.create-payment', ['invoiceId' => $invoice->id, 'paymentMethod' => 2]) }}" class="bg-secondary-500 text-white hover:bg-secondary py-2 px-4 rounded-md w-full bg-gradient-to-tr from-secondary via-50% via-20% via-secondary to-[#5573FD80] duration-300 text-center">
            銀行轉帳 (ATM)
        </a>
        
        <a href="{{ route('extensions.gateways.smilepay.create-payment', ['invoiceId' => $invoice->id, 'paymentMethod' => 4]) }}" class="bg-secondary-500 text-white hover:bg-secondary py-2 px-4 rounded-md w-full bg-gradient-to-tr from-secondary via-50% via-20% via-secondary to-[#5573FD80] duration-300 text-center">
            7-11 ibon 代碼
        </a>
        
        <a href="{{ route('extensions.gateways.smilepay.create-payment', ['invoiceId' => $invoice->id, 'paymentMethod' => 6]) }}" class="bg-secondary-500 text-white hover:bg-secondary py-2 px-4 rounded-md w-full bg-gradient-to-tr from-secondary via-50% via-20% via-secondary to-[#5573FD80] duration-300 text-center">
            全家 FamiPort 代碼
        </a>
    </div>
    
    @if(isset($debugMode) && $debugMode)
    <div class="mt-4 bg-gray-800 p-4 rounded text-xs">
        <h4 class="font-medium mb-2 text-yellow-500">除錯模式已啟用</h4>
        <p class="text-gray-400">訂單 ID: {{ $invoice->id }}</p>
        <p class="text-gray-400">訂單金額: {{ $total }}</p>
        <p class="text-gray-400">請求網址: {{ $current_url ?? url()->current() }}</p>
        <p class="text-gray-400">完整網址: {{ $full_url ?? url()->full() }}</p>
        <p class="text-gray-400">ATM 轉帳連結: {{ route('extensions.gateways.smilepay.create-payment', ['invoiceId' => $invoice->id, 'paymentMethod' => 2]) }}</p>
        <p class="text-gray-400">7-11 ibon 代碼連結: {{ route('extensions.gateways.smilepay.create-payment', ['invoiceId' => $invoice->id, 'paymentMethod' => 4]) }}</p>
        <p class="text-gray-400">全家 FamiPort 代碼連結: {{ route('extensions.gateways.smilepay.create-payment', ['invoiceId' => $invoice->id, 'paymentMethod' => 6]) }}</p>
    </div>
    @endif
</div>