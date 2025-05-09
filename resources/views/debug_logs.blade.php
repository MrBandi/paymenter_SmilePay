@extends('layouts.admin')

@section('title', 'SmilePay 除錯日誌')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">SmilePay 除錯日誌</h1>
        
        <div class="flex gap-2">
            @if($invoiceId)
                <a href="{{ route('admin.extensions.smilepay.debug-logs') }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                    查看所有日誌
                </a>
            @endif
            
            <form action="{{ route('admin.extensions.smilepay.clear-logs', ['invoiceId' => $invoiceId]) }}" method="POST" onsubmit="return confirm('確定要清除日誌嗎？此操作無法復原');">
                @csrf
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    清除日誌
                </button>
            </form>
        </div>
    </div>
    
    @if($invoiceId)
        <div class="mb-4 p-4 bg-blue-100 text-blue-800 rounded">
            正在查看訂單 ID: {{ $invoiceId }} 的日誌
        </div>
    @endif
    
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        時間
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        等級
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        訂單 ID
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        訊息
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        操作
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($logs as $log)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $log->created_at }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($log->level == 'error')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    錯誤
                                </span>
                            @elseif($log->level == 'warning')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    警告
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    信息
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($log->invoice_id)
                                <a href="{{ route('admin.extensions.smilepay.debug-logs', ['invoiceId' => $log->invoice_id]) }}" class="text-blue-600 hover:text-blue-900">
                                    {{ $log->invoice_id }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-md truncate">
                            {{ $log->message }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <button type="button" class="text-blue-600 hover:text-blue-900" onclick="showDetails({{ $log->id }})">
                                查看詳情
                            </button>
                        </td>
                    </tr>
                    <tr id="details-{{ $log->id }}" class="hidden bg-gray-50">
                        <td colspan="5" class="px-6 py-4">
                            <div class="text-sm">
                                <h3 class="font-medium text-gray-900 mb-2">詳細信息</h3>
                                <div class="bg-gray-100 p-3 rounded overflow-auto max-h-96">
                                    <pre class="text-xs">{{ $log->message }}</pre>
                                    @if($log->context)
                                        <div class="mt-2 pt-2 border-t border-gray-200">
                                            <h4 class="text-xs font-medium text-gray-700 mb-1">上下文數據:</h4>
                                            <pre class="text-xs">{{ json_encode(json_decode($log->context), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>

<script>
    function showDetails(id) {
        const detailsRow = document.getElementById(`details-${id}`);
        if (detailsRow.classList.contains('hidden')) {
            detailsRow.classList.remove('hidden');
        } else {
            detailsRow.classList.add('hidden');
        }
    }
</script>
@endsection
