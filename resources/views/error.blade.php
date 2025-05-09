<div class="mt-2 text-white">
    <div class="mb-4 text-red-500">
        <h3 class="font-medium mb-2">錯誤</h3>
        <p>{{ $error }}</p>
    </div>
    
    @if(isset($debugMode) && $debugMode && isset($debugInfo))
    <div class="mt-4 mb-4 bg-gray-800 p-4 rounded">
        <h4 class="font-medium mb-2 text-yellow-500">除錯信息</h4>
        <div class="text-xs overflow-auto" style="max-height: 300px;">
            <div class="mb-2">
                <span class="text-gray-400">錯誤信息:</span>
                <span class="text-red-400">{{ $debugInfo['message'] }}</span>
            </div>
            <div class="mb-2">
                <span class="text-gray-400">文件:</span>
                <span class="text-blue-400">{{ $debugInfo['file'] }}</span>
            </div>
            <div class="mb-2">
                <span class="text-gray-400">行號:</span>
                <span class="text-blue-400">{{ $debugInfo['line'] }}</span>
            </div>
            <div>
                <span class="text-gray-400">堆疊追蹤:</span>
                <pre class="mt-1 text-xs text-green-400 whitespace-pre-wrap">{{ $debugInfo['trace'] }}</pre>
            </div>
        </div>
    </div>
    @endif
    
    <a href="{{ route('invoices.pay', ['invoice' => $invoice->id, 'gateway' => 'smilepay']) }}" class="bg-secondary-500 text-white hover:bg-secondary py-2 px-4 rounded-md inline-block mt-4">
        重試
    </a>
</div>
