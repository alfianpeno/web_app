@php
$path=\App\Models\Utility::get_file('/uploads/bank_receipt');
@endphp
<div class="modal-body">
    <div class="row">
        <div class="col-12">
            <table class="table modal-table">
                <tr>
                    <th>{{__('Invoice Number')}}</th>
                    <td>{{ AUth::user()->invoiceNumberFormat($details->invoice_id) }}</td>

                </tr>
                <tr >
                    <th>{{__('Order Id')}}</th>
                    <td>{{ $BankTransfer->order_id }}</td>
                </tr>
                <tr>
                    <th>{{__('Amount')}}</th>
                    <td>{{ $BankTransfer->amount }}</td>
                </tr>
                <tr>
                    <th>{{__('Payment Type')}}</th>
                    <td>{{__('Bank Transfer')}}</td>
                </tr>
                <tr>
                    <th>{{__('Payment Status')}}</th>
                    <td>{{ $BankTransfer->status }}</td>
                </tr>
                <tr>
                    <th>{{__('Bank Details')}}</th>
                    <td>{!! $bank_detail !!}</td>
                </tr>
                @if(!empty( $BankTransfer->receipt))
                    <tr>
                        <th>{{__('Payment Receipt')}}</th>
                        <td>
                        <a  class="action-btn bg-primary ms-2 btn btn-sm align-items-center" href="{{ $path . '/' . $BankTransfer->receipt }}" download=""  data-bs-toggle="tooltip" title="{{ __('Download') }}">
                        <i class="ti ti-download text-white"></i>
                    </a>
                        </td>
                    </tr>
                @endif
            </table>
        </div>
    </div>

</div>
<div class="modal-footer">
    <a href="{{ route('action.status', [$BankTransfer->id, "Approval"]) }}"
        class="btn btn-success btn-xs">
        {{__('Approval')}}
    </a>
    <a href="{{ route('action.status', [$BankTransfer->id, "Rejected"]) }}"
        class="btn btn-danger btn-xs">
        {{__('Reject')}}
    </a>
</div>