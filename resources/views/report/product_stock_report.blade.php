@extends('layouts.admin')
@section('page-title')
    {{__('Product Stock')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Report')}}</li>
    <li class="breadcrumb-item">{{__('Product Stock Report')}}</li>
@endsection
@section('action-btn')
    <div class="d-flex">
        <a href="{{ route('productstock.export') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-file-export"></i>   
        </a>
    </div>
@endsection
{{-- @push('script-page')
<script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
<script>
    
    var filename = $('#filename').val();

    function saveAsPDF() {
        var element = document.getElementById('printableArea');
        var opt = {
            margin: 0.3,
            filename: filename,
            image: {type: 'jpeg', quality: 1},
            html2canvas: {scale: 4, dpi: 72, letterRendering: true},
            jsPDF: {unit: 'in', format: 'A4'}
        };
        console.log(opt);
        html2pdf().set(opt).from(element).save();

    }

</script>
@endpush --}}
@section('content')
{{-- <div id="printableArea"> --}}
    <div class="row">
        <div class="col-md-12">
            {{-- <input type="hidden" value="{{__('Product Stock Report')}}" id="filename"> --}}
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                            <tr>
                                <th>{{__('Date')}}</th>
                                <th>{{__('Product Name')}}</th>
                                <th>{{__('Quantity')}}</th>
                                <th>{{__('Type')}}</th>
                                <th>{{__('Description')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                                @foreach ($stocks as $stock)
                                    <tr>
                                        <td class="font-style">{{$stock->created_at->format('d M Y')}}</td>
                                        <td>{{ !empty($stock->product) ? $stock->product->name : '' }}
                                        <td class="font-style">{{ $stock->quantity }}</td>
                                        <td class="font-style">{{ ucfirst($stock->type) }}</td>
                                        <td class="font-style">{{$stock->description}}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
{{-- </div> --}}
@endsection