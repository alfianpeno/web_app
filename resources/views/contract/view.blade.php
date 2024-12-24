@extends('layouts.admin')
@push('script-page')
@endpush
@section('page-title')
    {{ __('Contract Detail') }}
@endsection
@section('title')
    <div class="d-inline-block">
        <h5 class="h4 d-inline-block font-weight-400 mb-0 ">{{ __('Contract Detail') }}</h5>
    </div>
@endsection
@section('breadcrumb')
    @if (\Auth::guard('customer')->check())
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">{{ __('Dashboard') }}</a></li>
    @else
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    @endif

    @if (\Auth::user()->type == 'company')
        <li class="breadcrumb-item active" aria-current="page"><a href="{{ route('contract.index') }}">{{ __('Contract') }}</a>
        </li>
    @else
        <li class="breadcrumb-item active" aria-current="page"><a
                href="{{ route('customer.contract.index') }}">{{ __('Contract') }}</a></li>
    @endif
    <li class="breadcrumb-item active" aria-current="page">{{ $contract->subject }}</li>
@endsection
@push('pre-purpose-css-page')
    <style>
        .nav-tabs .nav-link-tabs.active {
            background: none;
        }
    </style>
@endpush

@push('css-page')
    <link rel="stylesheet" href="{{asset('css/summernote/summernote-bs4.css')}}">
@endpush
@push('script-page')
    <script src="{{asset('css/summernote/summernote-bs4.js')}}"></script>
    <script>
        $(document).ready(function() {
            $('.summernote-simple').summernote({
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'strikethrough']],
                    ['list', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'unlink']],
                ],
                height: 200,
            });
        });
    </script>
    <script>
        var scrollSpy = new bootstrap.ScrollSpy(document.body, {
            target: '#useradd-sidenav',
            offset: 300
        })
    </script>

    @php
        if (\Auth::user()->type == 'company') {
            if (\Auth::user()->can('upload attachment')) {
                $route = route('contract.file.upload', [$contract->id]);
            }
        } else {
            $route = route('customer.contract.file.upload', [$contract->id]);
        }
    @endphp
    <script>
        Dropzone.autoDiscover = false;
        myDropzone = new Dropzone("#my-dropzone", {

            url: "{{ $route }}",

            success: function(file, response) {
                location.reload();
                if (response.is_success) {
                    dropzoneBtn(file, response);
                    show_toastr('{{ __('success') }}', 'Attachment Create Successfully!', 'success');
                } else {
                    myDropzone.removeFile(file);
                    show_toastr('{{ __('error') }}', 'This operation is not perform due to demo mode.',
                        'error');


                }
            },
            error: function(file, response) {
                myDropzone.removeFile(file);
                if (response.error) {
                    show_toastr('{{ __('Error') }}', response.error, 'error');
                } else {
                    show_toastr('{{ __('Error') }}', response.error, 'error');
                }
            }
        });
        myDropzone.on("sending", function(file, xhr, formData) {
            formData.append("_token", $('meta[name="csrf-token"]').attr('content'));
            formData.append("contract_id", {{ $contract->id }});
        });

        function dropzoneBtn(file, response) {
            var download = document.createElement('a');
            download.setAttribute('href', response.download);
            download.setAttribute('class', "action-btn btn-primary mx-1 mt-1 btn btn-sm d-inline-flex align-items-center");
            download.setAttribute('data-toggle', "tooltip");
            download.setAttribute('data-original-title', "{{ __('Download') }}");
            download.innerHTML = "<i class='fas fa-download'></i>";

            var del = document.createElement('a');
            del.setAttribute('href', response.delete);
            del.setAttribute('class', "action-btn btn-danger mx-1 mt-1 btn btn-sm d-inline-flex align-items-center");
            del.setAttribute('data-toggle', "tooltip");
            del.setAttribute('data-original-title', "{{ __('Delete') }}");
            del.innerHTML = "<i class='ti ti-trash'></i>";

            del.addEventListener("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (confirm("Are you sure ?")) {
                    var btn = $(this);
                    $.ajax({
                        url: btn.attr('href'),
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        type: 'DELETE',
                        success: function(response) {
                            if (response.is_success) {
                                btn.closest('.dz-image-preview').remove();
                            } else {
                                show_toastr('{{ __('Error') }}', response.error, 'error');
                            }
                        },
                        error: function(response) {
                            response = response.responseJSON;
                            if (response.is_success) {
                                show_toastr('{{ __('Error') }}', response.error, 'error');
                            } else {
                                show_toastr('{{ __('Error') }}', response.error, 'error');
                            }
                        }
                    })
                }
            });

            var html = document.createElement('div');
            html.setAttribute('class', "text-center mt-10");
            html.appendChild(download);
            html.appendChild(del);

            file.previewTemplate.appendChild(html);
        }
        @foreach ($contract->files as $file)
        @endforeach
    </script>

    <script>
        $(document).on('click', '#comment_submit', function(e) {
            var curr = $(this);

            var comment = $.trim($("#form-comment textarea[name='comment']").val());

            if (comment != '') {
                $.ajax({
                    url: $("#form-comment").data('action'),
                    data: {
                        comment: comment,
                        "_token": "{{ csrf_token() }}",
                    },
                    type: 'POST',
                    success: function(data) {
                        location.reload();
                        data = JSON.parse(data);
                        console.log(data);
                        var html = "<div class='list-group-item px-0'>" +
                            "                    <div class='row align-items-center'>" +
                            "                        <div class='col-auto'>" +
                            "                            <a href='#' class='avatar avatar-sm rounded-circle ms-2'>" +
                            "                                <img src=" + data.default_img +
                            " alt='' class='avatar-sm rounded-circle'>" +
                            "                            </a>" +
                            "                        </div>" +
                            "                        <div class='col ml-n2'>" +
                            "                            <p class='d-block h6 text-sm font-weight-light mb-0 text-break'>" +
                            data.comment + "</p>" +
                            "                            <small class='d-block'>" + data.current_time +
                            "</small>" +
                            "                        </div>" +
                            "                        <div class='action-btn bg-danger me-4'><div class='col-auto'><a href='#' class='mx-3 btn btn-sm  align-items-center delete-comment' data-url='" +
                            data.deleteUrl +
                            "'><i class='ti ti-trash text-white'></i></a></div></div>" +
                            "                    </div>" +
                            "                </div>";

                        $("#comments").prepend(html);
                        $("#form-comment textarea[name='comment']").val('');
                        load_task(curr.closest('.task-id').attr('id'));
                        show_toastr('success', 'Comment Added Successfully!');
                    },
                    error: function(data) {
                        show_toastr('error', 'Some Thing Is Wrong!');
                    }
                });
            } else {
                show_toastr('error', 'Please add comment!');
            }
        });

        $(document).on("click", ".delete-comment", function() {
            var btn = $(this);

            $.ajax({
                url: $(this).attr('data-url'),
                type: 'DELETE',
                dataType: 'JSON',
                data: {
                    comment: comment,
                    "_token": "{{ csrf_token() }}",
                },
                success: function(data) {
                    location.reload();
                    load_task(btn.closest('.task-id').attr('id'));
                    show_toastr('success', 'Comment Deleted Successfully!');
                    btn.closest('.list-group-item').remove();
                },
                error: function(data) {
                    data = data.responseJSON;
                    if (data.message) {
                        show_toastr('error', data.message);
                    } else {
                        show_toastr('error', 'Some Thing Is Wrong!');
                    }
                }
            });
        });
    </script>

    <script>
        $(document).on('click', '#note_submit', function(e) {
            var curr = $(this);

            var note = $.trim($("#form-note textarea[name='note']").val());

            if (note != '') {
                $.ajax({
                    url: $("#form-note").data('action'),
                    data: {
                        note: note,
                        "_token": "{{ csrf_token() }}",
                    },
                    type: 'POST',
                    success: function(data) {
                        location.reload();
                        data = JSON.parse(data);
                        console.log(data);
                        var html = "<div class='list-group-item px-0'>" +
                            "                    <div class='row align-items-center'>" +
                            "                        <div class='col-auto'>" +
                            "                            <a href='#' class='avatar avatar-sm rounded-circle ms-2'>" +
                            "                                <img src=" + data.default_img +
                            " alt='' class='avatar-sm rounded-circle'>" +
                            "                            </a>" +
                            "                        </div>" +
                            "                        <div class='col ml-n2'>" +
                            "                            <p class='d-block h6 text-sm font-weight-light mb-0 text-break'>" +
                            data.note + "</p>" +
                            "                            <small class='d-block'>" + data.current_time +
                            "</small>" +
                            "                        </div>" +
                            "                        <div class='action-btn bg-danger me-4'><div class='col-auto'><a href='#' class='mx-3 btn btn-sm  align-items-center delete-note' data-url='" +
                            data.deleteUrl +
                            "'><i class='ti ti-trash text-white'></i></a></div></div>" +
                            "                    </div>" +
                            "                </div>";

                        $("#comments").prepend(html);
                        $("#form-note textarea[name='note']").val('');
                        load_task(curr.closest('.task-id').attr('id'));
                        show_toastr('success', 'note Added Successfully!');
                    },
                    error: function(data) {
                        show_toastr('error', 'Some Thing Is Wrong!');
                    }
                });
            } else {
                show_toastr('error', 'Please add Note!');
            }
        });
        $(document).on("click", ".delete-note", function() {
            var btn = $(this);

            $.ajax({
                url: $(this).attr('data-url'),
                type: 'DELETE',
                dataType: 'JSON',
                data: {
                    note: note,
                    "_token": "{{ csrf_token() }}",
                },
                success: function(data) {
                    load_task(btn.closest('.task-id').attr('id'));
                    show_toastr('success', 'note Deleted Successfully!');
                    btn.closest('.list-group-item').remove();
                },
                error: function(data) {
                    data = data.responseJSON;
                    if (data.message) {
                        show_toastr('error', data.message);
                    } else {
                        show_toastr('error', 'Some Thing Is Wrong!');
                    }
                }
            });
        });
    </script>

    <script>
        $(document).on("click", ".status", function() {

            var edit_status = $(this).attr('data-id');
            var url = $(this).attr('data-url');
            $.ajax({
                url: url,
                type: 'POST',
                data: {

                    "edit_status": edit_status,
                    "_token": "{{ csrf_token() }}",
                },
                success: function(data) {
                    show_toastr('{{ __('Success') }}', 'Status Update Successfully!', 'success');
                    location.reload();
                }

            });
        });
    </script>
@endpush
@php
    $chatGPT = \App\Models\Utility::settings('enable_chatgpt');
    $enable_chatgpt = !empty($chatGPT);
@endphp

@section('action-btn')

    <div class="col-md-6 float-end d-flex col-md-6 text-end d-flex align-items-center justify-content-end mb-4 ">
        @if (\Auth::user()->can('send contract mail') && $contract->edit_status == 'accept')
            <div class="action-btn ms-2 ">
                <a href="{{ route('send.mail.contract', $contract->id) }}" class="btn btn-sm btn-primary btn-icon"
                    data-bs-toggle="tooltip" data-bs-original-title="{{ __('Send Email') }}">
                    <i class="ti ti-mail text-white"></i>
                </a>
            </div>
        @endif

        @if (\Auth::user()->can('duplicate contract') && $contract->edit_status == 'accept')
            <div class="action-btn ms-2">
                <a href="#" data-size="lg" data-url="{{ route('contract.duplicate', $contract->id) }}"
                    data-ajax-popup="true" data-title="{{ __('Duplicate Contract') }}"
                    class="btn btn-sm btn-primary btn-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                    title="{{ __('Duplicate') }}"><i class="ti ti-copy text-white"></i></a>
            </div>
        @endif

        @if (\Auth::user()->type == 'company')
            <div class="action-btn ms-2">
                <a href="{{ route('contract.download.pdf', \Crypt::encrypt($contract->id)) }}"
                    class="btn btn-sm btn-primary btn-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                    title="{{ __('Download') }}" target="_blanks"><i class="ti ti-download text-white"></i></a>
            </div>
        @else
            <div class="action-btn ms-2">
                <a href="{{ route('customer.contract.download.pdf', \Crypt::encrypt($contract->id)) }}"
                    class="btn btn-sm btn-primary btn-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                    title="{{ __('Download') }}" target="_blanks"><i class="ti ti-download text-white"></i></a>
            </div>
        @endif

        @if (\Auth::user()->type == 'company')
            <div class="action-btn ms-2">
                <a href="{{ route('get.contract', $contract->id) }}" target="_blank"
                    class="btn btn-sm btn-primary btn-icon" title="{{ __('Preview') }}" data-bs-toggle="tooltip"
                    data-bs-placement="top">
                    <i class="ti ti-eye"></i>
                </a>
            </div>
        @else
            <div class="action-btn ms-2">
                <a href="{{ route('customer.get.contract', $contract->id) }}" target="_blank"
                    class="btn btn-sm btn-primary btn-icon" title="{{ __('Preview') }}" data-bs-toggle="tooltip"
                    data-bs-placement="top">
                    <i class="ti ti-eye"></i>
                </a>
            </div>
        @endif
        @if (\Auth::user()->type == 'company' && $contract->edit_status == 'accept')
            <div class="action-btn ms-2">
                <a href="#" class="btn btn-sm btn-primary btn-icon"
                    data-url="{{ route('signature', $contract->id) }}" data-ajax-popup="true"
                    data-title="{{ __('Create Signature') }}" data-size="md" title="{{ __('Signature') }}"
                    data-bs-toggle="tooltip" data-bs-placement="top">
                    <i class="ti ti-pencil"></i>
                </a>
            </div>
        @endif
        @if (\Auth::user()->type != 'company' && $contract->edit_status == 'accept')
            <div class="action-btn ms-2">
                <a href="#" class="btn btn-sm btn-primary btn-icon"
                    data-url="{{ route('customer.signature', $contract->id) }}" data-ajax-popup="true"
                    data-title="{{ __('Create Signature') }}" data-size="md" title="{{ __('Signature') }}"
                    data-bs-toggle="tooltip" data-bs-placement="top">
                    <i class="ti ti-pencil"></i>
                </a>
            </div>
        @endif

        @php
            $editstatus = App\Models\Contract::editstatus();
        @endphp

        @if (\Auth::user()->type != 'company')
            <ul class="list-unstyled mb-0 ms-1">
                <li class="dropdown dash-h-item drp-language">
                    <a class="dash-head-link dropdown-toggle arrow-none me-0 ms-0 p-2 rounded-1" data-bs-toggle="dropdown"
                        href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <span class="drp-text hide-mob">
                            <i class=" drp-arrow nocolor hide-mob">{{ ucfirst($contract->edit_status) }}<span
                                    class="ti ti-chevron-down"></span></i>
                    </a>
                    <div class="dropdown-menu dash-h-dropdown">
                        @foreach ($editstatus as $k => $status)
                            <a class="dropdown-item status" data-id="{{ $k }}"
                                data-url="{{ route('customer.contract.status', $contract->id) }}"
                                href="#">{{ ucfirst($status) }}
                            </a>
                        @endforeach
                    </div>
                </li>
            </ul>
        @endif

        {{-- @if (\Auth::user()->type != 'company')
        <ul class="list-unstyled mb-0 ms-1">
            <li class="dropdown dash-h-item drp-language">
                <a class="dash-head-link dropdown-toggle arrow-none me-0 ms-0 p-2 rounded-1" data-bs-toggle="dropdown" href="#"
                    role="button" aria-haspopup="false" aria-expanded="false">
                    <span class="drp-text hide-mob">
                        <i class=" drp-arrow nocolor hide-mob">{{ ucfirst($contract->edit_status) }}<span class="ti ti-chevron-down"></span></i>
                </a>
                <div class="dropdown-menu dash-h-dropdown">
                    @foreach ($editstatus as $k => $status)
                        <a class="dropdown-item status" data-id="{{ $k }}"
                            data-url="{{ route('customer.contract.status', $contract->id) }}"
                            href="#">{{ ucfirst($status) }}</a>
                    @endforeach
                </div>
            </li>
        </ul>
        @endif --}}


    </div>

@endsection
@section('filter')
@endsection

@section('content')

    <div class="row">
        <div class="col-sm-12">
            <div class="row">
                <div class="col-xl-3">
                    <div class="card sticky-top" style="top:30px">
                        <div class="list-group list-group-flush" id="useradd-sidenav">
                            <a href="#general"
                                class="list-group-item list-group-item-action border-0">{{ __('General') }} <div
                                    class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                            <a href="#attachments"
                                class="list-group-item list-group-item-action border-0">{{ __('Attachment') }} <div
                                    class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                            <a href="#comment"
                                class="list-group-item list-group-item-action border-0">{{ __('Comment') }} <div
                                    class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                            <a href="#notes" class="list-group-item list-group-item-action border-0">{{ __('Notes') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-xl-9">

                    <div id="general">
                        <div class="row">
                            <div class="col-xl-7">
                                <div class="row">
                                    <div class="col-lg-4 col-6">
                                        <div class="card">
                                            <div class="card-body" style="min-height: 205px;">
                                                <div class="theme-avtar bg-primary">
                                                    <i class="ti ti-user-plus"></i>
                                                </div>
                                                <h6 class="mb-3 mt-4">{{ __('Attachment') }}</h6>
                                                <h3 class="mb-0">{{ count($contract->files) }}</h3>
                                                <h3 class="mb-0"></h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-6">
                                        <div class="card">
                                            <div class="card-body" style="min-height: 205px;">
                                                <div class="theme-avtar bg-info">
                                                    <i class="ti ti-click"></i>
                                                </div>
                                                <h6 class="mb-3 mt-4">{{ __('Comment') }}</h6>
                                                <h3 class="mb-0">{{ count($contract->comment) }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-6">
                                        <div class="card">
                                            <div class="card-body" style="min-height: 205px;">
                                                <div class="theme-avtar bg-warning">
                                                    <i class="ti ti-file"></i>
                                                </div>
                                                <h6 class="mb-3 mt-4 ">{{ __('Notes') }}</h6>
                                                <h3 class="mb-0">{{ count($contract->note) }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xxl-5">
                                <div class="card report_card total_amount_card">
                                    <div class="card-body pt-0" style="margin-bottom: -30px; margin-top: -10px;">

                                        <address class="mb-0 text-sm">
                                            <dl class="row mt-4 align-items-center">
                                                <dt class="col-sm-4 h6 text-sm">{{ __('Customer Name') }}</dt>
                                                <dd class="col-sm-8 text-sm"> {{ $contract->clients->name ?? '' }}</dd>

                                                <dt class="col-sm-4 h6 text-sm">{{ __('Subject') }}</dt>
                                                <dd class="col-sm-8 text-sm"> {{ $contract->subject }}</dd>

                                                <dt class="col-sm-4 h6 text-sm">{{ __('Value') }}</dt>
                                                <dd class="col-sm-8 text-sm">
                                                    {{ Auth::user()->priceFormat($contract->value) }}</dd>

                                                <dt class="col-sm-4 h6 text-sm">{{ __('Type') }}</dt>
                                                <dd class="col-sm-8 text-sm">{{ $contract->types->name }}</dd>

                                                <dt class="col-sm-4 h6 text-sm">{{ __('Start Date') }}</dt>
                                                <dd class="col-sm-8 text-sm">
                                                    {{ Auth::user()->dateFormat($contract->start_date) }}</dd>

                                                <dt class="col-sm-4 h6 text-sm">{{ __('End Date') }}</dt>
                                                <dd class="col-sm-8 text-sm">
                                                    {{ Auth::user()->dateFormat($contract->end_date) }}</dd>

                                                <dt class="col-sm-4 h6 text-sm">{{ __('Status') }}</dt>
                                                <dd class="col-sm-8 text-sm">{{ ucfirst($contract->edit_status) }}</dd>
                                            </dl>
                                        </address>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">{{ __('Description') }}</h5>
                            </div>
                            @if ($enable_chatgpt)
                                <div class="p-2 m-2">
                                    <a href="javascript:void(0)" data-size="md" data-ajax-popup-over="true"
                                        data-url="{{ route('generate', ['contract']) }}" data-bs-toggle="tooltip"
                                        data-bs-placement="top" title="{{ __('Generate') }}"
                                        data-title="{{ __('Generate content with AI') }}"
                                        class="btn btn-primary btn-sm float-end">
                                        <i class="fas fa-robot"></i>
                                        {{ __('Generate with AI') }}
                                    </a>
                                </div>
                            @endif
                            <div class="card-body p-3">
                                @if (\Auth::user()->type == 'company')
                                    @if (\Auth::user()->can('contract description'))
                                        {{ Form::open(['route' => ['contract.description.store', $contract->id]]) }}
                                        <div class="form-group mt-3">
                                            <textarea class="summernote-simple" name="notes" id="notes" rows="8">{!! $contract->notes !!}</textarea>
                                        </div>
                                        @if ($contract->edit_status == 'accept')
                                            <div class="col-md-12 text-end mb-0">
                                                {{ Form::submit(__('Add'), ['class' => 'btn  btn-primary']) }}
                                            </div>
                                        @endif
                                        {{ Form::close() }}
                                    @endif
                                @else
                                    @if (\Auth::user()->can('contract description'))
                                        {{ Form::open(['route' => ['customer.contract.description.store', $contract->id]]) }}
                                        <div class="form-group mt-3">
                                            <textarea class="summernote-simple" name="notes" id="notes" rows="8">{!! $contract->notes !!}</textarea>
                                        </div>

                                        @if (\Auth::user()->type == 'company')
                                            <div class="col-md-12 text-end mb-0">
                                                {{ Form::submit(__('Add'), ['class' => 'btn  btn-primary']) }}
                                            </div>
                                        @endif
                                        {{ Form::close() }}
                                    @endif
                                @endif
                            </div>

                        </div>

                    </div>

                    <div id="attachments">
                        <div class="row ">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>{{ __('Attachments') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        @if (\Auth::user()->type=='company')
                                            <div class=" ">
                                                <div class="col-md-12 dropzone browse-file mb-3" id="my-dropzone"></div>
                                            </div>
                                        @elseif(\Auth::user()->type != 'company' && $contract->edit_status=='accept' )
                                            <div class=" ">
                                                <div class="col-md-12 dropzone browse-file mb-3" id="my-dropzone"></div>
                                            </div>
                                        @endif
                                        @foreach ($contract->files as $file)
                                            <div class="card mb-3 border shadow-none">
                                                <div class="px-3 py-3">
                                                    <div class="row align-items-center">
                                                        <div class="col">
                                                            <h6 class="text-sm mb-0">
                                                                <a href="#!">{{ $file->files }}</a>
                                                            </h6>
                                                            <p class="card-text small text-muted">
                                                                {{ number_format(\File::size(storage_path('contract_attachment/' . $file->files)) / 1048576, 2) . ' ' . __('MB') }}
                                                            </p>
                                                        </div>

                                                        <div class="col-auto actions">

                                                            @if (\Auth::user()->can('delete attachment') && $contract->edit_status == 'accept')
                                                                <div class="action-btn es-2">
                                                                    {!! Form::open(['method' => 'DELETE', 'route' => ['contract.file.delete', $contract->id, $file->id]]) !!}
                                                                    <a href="#!"
                                                                        class="mx-3 btn btn-sm  align-items-center bs-pass-para bg-danger">
                                                                        <i class="ti ti-trash text-white"></i>
                                                                    </a>
                                                                    {!! Form::close() !!}
                                                                </div>
                                                            @endif

                                                            @if (\Auth::user()->type != 'company' && $contract->edit_status == 'accept' && \Auth::user()->id == $file->created_by)
                                                                <div class="action-btn me-2">
                                                                    {!! Form::open(['method' => 'DELETE', 'route' => ['customer.contract.file.delete', $contract->id, $file->id]]) !!}
                                                                    <a href="#!"
                                                                        class="mx-3 btn btn-sm  align-items-center bs-pass-para bg-danger">
                                                                        <i class="ti ti-trash text-white"></i>
                                                                    </a>
                                                                    {!! Form::close() !!}
                                                                </div>
                                                            @endif
                                                        </div>
                                                      
                                                        <!-- <div class="action-btn bg-warning"> -->
                                                            @php
                                                                $attachments = \App\Models\Utility::get_file('contract_attachment');

                                                            @endphp
                                                            <div class="action-btn me-2">
                                                                <a href="{{ $attachments . '/' . $file->files }}"
                                                                    class=" btn btn-sm d-inline-flex align-items-center bg-warning"
                                                                    download="" data-bs-toggle="tooltip"
                                                                    title="Download">
                                                                    <span class="text-white"><i
                                                                            class="ti ti-download"></i></span>
                                                                </a>
                                                            </div>
                                                           
                                                        <!-- </div> -->

                                                        @if ($contract->files)
                                                            <div class="action-btn me-2">
                                                                <a href="{{ asset(Storage::url('contract_attachment')) . '/' . $file->files }}"
                                                                    class=" btn btn-sm d-inline-flex align-items-center bg-secondary"
                                                                    target="_blank" data-bs-toggle="tooltip"
                                                                    title="Preview">
                                                                    <span class="text-white"><i
                                                                            class="ti ti-crosshair"></i></span>
                                                                </a>
                                                            </div>
                                                        @endif
                                                    </div> 
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>


                    <div id="comment" role="tabpanel" aria-labelledby="pills-comments-tab">
                        <div class="row pt-2">
                            <div class="col-12">
                                <div id="comment">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>{{ __('Comments') }}</h5>
                                        </div>
                                        <div class="card-body">
                                            {{-- @if ($contract->edit_status == 'accept') --}}
                                                @if (\Auth::user()->type == 'company')
                                                    @if (\Auth::user()->can('add comment'))
                                                        <div class="col-12 d-flex">
                                                            <div class="form-group mb-0 form-send w-100">
                                                                <form method="post" class="card-comment-box"
                                                                    id="form-comment"
                                                                    data-action="{{ route('comment.store', [$contract->id]) }}">
                                                                    <textarea rows="1" id="comment" class="form-control" name="comment" data-toggle="autosize"
                                                                        placeholder="Add a comment..." spellcheck="false"></textarea>
                                                                    <grammarly-extension data-grammarly-shadow-root="true"
                                                                        style="position: absolute; top: 0px; left: 0px; pointer-events: none; z-index: 1;"
                                                                        class="cGcvT"></grammarly-extension>
                                                                    <grammarly-extension data-grammarly-shadow-root="true"
                                                                        style="mix-blend-mode: darken; position: absolute; top: 0px; left: 0px; pointer-events: none; z-index: 1;"
                                                                        class="cGcvT"></grammarly-extension>
                                                                </form>
                                                            </div>
                                                            <button id="comment_submit"
                                                                class="btn btn-send shadow-none"><i
                                                                    class="f-16 text-primary ti ti-brand-telegram">
                                                                </i>
                                                            </button>
                                                        </div>
                                                    @endif
                                                @elseif(\Auth::user()->type != 'company' && $contract->edit_status=='accept')
                                                    @if (\Auth::user()->can('add comment'))
                                                        <div class="col-12 d-flex">
                                                            <div class="form-group mb-0 form-send w-100">
                                                                <form method="post" class="card-comment-box"
                                                                    id="form-comment"
                                                                    data-action="{{ route('customer.comment.store', [$contract->id]) }}">
                                                                    <textarea rows="1" class="form-control" name="comment" data-toggle="autosize" placeholder="Add a comment..."
                                                                        spellcheck="false"></textarea>
                                                                    <grammarly-extension data-grammarly-shadow-root="true"
                                                                        style="position: absolute; top: 0px; left: 0px; pointer-events: none; z-index: 1;"
                                                                        class="cGcvT"></grammarly-extension>
                                                                    <grammarly-extension data-grammarly-shadow-root="true"
                                                                        style="mix-blend-mode: darken; position: absolute; top: 0px; left: 0px; pointer-events: none; z-index: 1;"
                                                                        class="cGcvT"></grammarly-extension>
                                                                </form>
                                                            </div>
                                                            <button id="comment_submit"
                                                                class="btn btn-send shadow-none"><i
                                                                    class="f-16 text-primary ti ti-brand-telegram">
                                                                </i>
                                                            </button>
                                                        </div>
                                                    @endif
                                                @endif
                                            {{-- @endif --}}

                                            <div class="">
                                                <div class="list-group list-group-flush mb-0" id="comments">
                                                    @foreach ($contract->comment as $comment)
                                                        <div class="list-group-item px-0">
                                                            <div class="row align-items-center">
                                                                <div class="col-auto">
                                                                    @if ($comment->type == 'company')
                                                                        @php
                                                                            $user = \App\Models\User::find($comment->created_by);

                                                                            $profile = \App\Models\Utility::get_file('/');
                                                                        @endphp

                                                                        <a href="{{ !empty($user->avatar) ? $profile . $user->avatar : $profile . '/avatar.png' }}"
                                                                            target="_blank"
                                                                            class="avatar avatar-sm rounded-circle">

                                                                            <img class="rounded-circle" width="50"
                                                                                height="50"
                                                                                src="{{ !empty($user->avatar) ? $profile . $user->avatar : $profile . '/avatar.png' }}">
                                                                        </a>
                                                                    @elseif($comment->type == 'customer')
                                                                        @php

                                                                            $customer = \App\Models\Customer::find($comment->created_by);

                                                                            $profile = \App\Models\Utility::get_file('uploads/avatar/');
                                                                        @endphp

                                                                        <a href="{{ !empty($customer->avatar) ? $profile . $customer->avatar : $profile . '/avatar.png' }}"
                                                                            target="_blank"
                                                                            class="avatar avatar-sm rounded-circle">

                                                                            <img class="rounded-circle" width="50"
                                                                                height="50"
                                                                                src="{{ !empty($customer->avatar) ? $profile . $customer->avatar : $profile . '/avatar.png' }}">
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                                <div class="col ml-n2">
                                                                    <p
                                                                        class="d-block h6 text-sm font-weight-light mb-0 text-break">
                                                                        {{ $comment->comment }}</p>
                                                                    <small
                                                                        class="d-block">{{ $comment->created_at->diffForHumans() }}</small>
                                                                </div>
                                                                @if (\Auth::user()->can('delete comment') && $contract->edit_status == 'accept')
                                                                    <div class="col-auto">
                                                                        {!! Form::open(['method' => 'GET', 'route' => ['comment.destroy', $comment->id]]) !!}
                                                                        <a href="#!"
                                                                            class="mx-3 btn btn-sm d-inline-flex align-items-center bs-pass-para btn-danger"
                                                                            data-bs-toggle="tooltip"
                                                                            data-bs-placement="top"
                                                                            title="{{ __('Delete') }}">
                                                                            <span class="text-white"> <i
                                                                                    class="ti ti-trash"></i></span>
                                                                        </a>
                                                                        {!! Form::close() !!}
                                                                    </div>
                                                                @endif


                                                                @if (
                                                                    \Auth::user()->type != 'company' &&
                                                                        $contract->edit_status == 'accept' &&
                                                                        \Auth::user()->id == $comment->created_by &&
                                                                        Auth::guard('customer')->user()->email == \Auth::user()->email)
                                                                    <div class="col-auto">
                                                                        {!! Form::open(['method' => 'GET', 'route' => ['customer.comment.destroy', $comment->id]]) !!}
                                                                        <a href="#!"
                                                                            class="mx-3 btn btn-sm d-inline-flex align-items-center bs-pass-para btn-danger"
                                                                            data-bs-toggle="tooltip"
                                                                            data-bs-placement="top"
                                                                            title="{{ __('Delete') }}">
                                                                            <span class="text-white"> <i
                                                                                    class="ti ti-trash"></i></span>
                                                                        </a>
                                                                        {!! Form::close() !!}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="notes" role="tabpanel" aria-labelledby="pills-comments-tab">
                        <div class="row pt-2">
                            <div class="col-12">
                                <div id="notes">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>{{ __('Notes') }}</h5>
                                        </div>
                                        @if ($enable_chatgpt)
                                            <div class="p-2 m-2">

                                                <a href="javascript:void(0)" data-size="md" data-ajax-popup-over="true"
                                                    data-url="{{ route('generate', ['contract']) }}"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="{{ __('Generate') }}"
                                                    data-title="{{ __('Generate content with AI') }}"
                                                    class="btn btn-primary btn-sm float-end">
                                                    <i class="fas fa-robot"></i>
                                                    {{ __('Generate with AI') }}
                                                </a>

                                                <a href="javascript:void(0)" data-size="md" data-ajax-popup-over="true"
                                                    data-url="{{ route('grammar', ['grammar']) }}"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="{{ __('Generate Grammar') }}"
                                                    data-title="{{ __('Grammar check with AI') }}"
                                                    class="btn btn-primary btn-sm float-end" style="margin-right: 5px;">
                                                    <i class="ti ti-rotate"></i>
                                                    {{ __('Grammar check with AI') }}
                                                </a>

                                            </div>
                                        @endif
                                        <div class="card-body">
                                            {{-- @if ($contract->edit_status == 'accept') --}}
                                                @if (\Auth::user()->type == 'company')
                                                    @if (\Auth::user()->can('add notes'))
                                                        {{ Form::open(['route' => ['contract.note.store', $contract->id]]) }}
                                                        <div class="form-group ">
                                                            <textarea rows="3" class="form-control tox-target summernotes grammer_textarea" name="note"
                                                                data-toggle="autosize" id="summernote" placeholder="Add a note..." spellcheck="false"></textarea>
                                                            <grammarly-extension data-grammarly-shadow-root="true"
                                                                style="position: absolute; top: 0px; left: 0px; pointer-events: none; z-index: 1;"
                                                                class="cGcvT"></grammarly-extension>
                                                            <grammarly-extension data-grammarly-shadow-root="true"
                                                                style="mix-blend-mode: darken; position: absolute; top: 0px; left: 0px; pointer-events: none; z-index: 1;"
                                                                class="cGcvT"></grammarly-extension>
                                                        </div>
                                                        {{-- @if ($contract->edit_status == 'accept') --}}
                                                            <div class="col-md-12 text-end mb-0">
                                                                {{ Form::submit(__('Add'), ['class' => 'btn  btn-primary']) }}
                                                            </div>
                                                        {{-- @endif --}}
                                                        {{ Form::close() }}
                                                    @endif
                                                @elseif(\Auth::user()->type != 'company' && $contract->edit_status=='accept')
                                                    @if (\Auth::user()->can('add notes'))
                                                        {{ Form::open(['route' => ['customer.contract.note.store', $contract->id]]) }}
                                                        <div class="form-group">
                                                            <textarea rows="3" class="form-control tox-target summernotes grammer_textarea" name="note"
                                                                data-toggle="autosize" id="summernote" placeholder="Add a note..." spellcheck="false"></textarea>
                                                            <grammarly-extension data-grammarly-shadow-root="true"
                                                                style="position: absolute; top: 0px; left: 0px; pointer-events: none; z-index: 1;"
                                                                class="cGcvT"></grammarly-extension>
                                                            <grammarly-extension data-grammarly-shadow-root="true"
                                                                style="mix-blend-mode: darken; position: absolute; top: 0px; left: 0px; pointer-events: none; z-index: 1;"
                                                                class="cGcvT"></grammarly-extension>
                                                        </div>
                                                        {{-- @if ($contract->edit_status == 'accept') --}}
                                                            <div class="col-md-12 text-end mb-0">
                                                                {{ Form::submit(__('Add'), ['class' => 'btn  btn-primary']) }}
                                                            </div>
                                                        {{-- @endif --}}
                                                        {{ Form::close() }}
                                                    @endif

                                                @endif
                                            {{-- @endif --}}

                                            <div class="">
                                                <div class="list-group list-group-flush mb-0" id="comments">
                                                    @foreach ($contract->note as $note)
                                                        <div class="list-group-item ">
                                                            <div class="row align-items-center">
                                                                {{-- <div class="col-auto">
                                                            <a href="@if ($employee->avatar) {{asset('/storage/avatars/'.$employee->avatar)}} @else {{asset('custom/img/avatar/avatar-1.png')}} @endif" target="_blank" class="avatar avatar-sm rounded-circle">
                                                                <img  class="rounded-circle"  width="50" height="50" src="@if ($employee->avatar) {{asset('/storage/avatars/'.$employee->avatar)}} @else {{asset('custom/img/avatar/avatar-1.png')}} @endif" title="{{ $contract->employee->name }}">
                                                            </a>
                                                        </div> --}}
                                                                <div class="col-auto">
                                                                    @if ($note->type == 'company')
                                                                        @php
                                                                            $user = \App\Models\User::find($note->created_by);

                                                                            $profile = \App\Models\Utility::get_file('/');
                                                                        @endphp

                                                                        <a href="{{ !empty($user->avatar) ? $profile . $user->avatar : $profile . '/avatar.png' }}"
                                                                            target="_blank"
                                                                            class="avatar avatar-sm rounded-circle">

                                                                            <img class="rounded-circle" width="50"
                                                                                height="50"
                                                                                src="{{ !empty($user->avatar) ? $profile . $user->avatar : $profile . '/avatar.png' }}">
                                                                        </a>
                                                                    @elseif($note->type == 'customer')
                                                                        @php

                                                                            $customer = \App\Models\Customer::find($note->created_by);

                                                                            $profile = \App\Models\Utility::get_file('uploads/avatar/');
                                                                        @endphp

                                                                        <a href="{{ !empty($customer->avatar) ? $profile . $customer->avatar : $profile . '/avatar.png' }}"
                                                                            target="_blank"
                                                                            class="avatar avatar-sm rounded-circle">

                                                                            <img class="rounded-circle" width="50"
                                                                                height="50"
                                                                                src="{{ !empty($customer->avatar) ? $profile . $customer->avatar : $profile . '/avatar.png' }}">
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                                <div class="col ml-n2">
                                                                    <p
                                                                        class="d-block h6 text-sm font-weight-light mb-0 text-break">
                                                                        {{ $note->note }}</p>
                                                                    <small
                                                                        class="d-block">{{ $note->created_at->diffForHumans() }}</small>
                                                                </div>

                                                                @if (\Auth::user()->can('delete notes') && $contract->edit_status == 'accept')
                                                                    <div class="col-auto">
                                                                        {!! Form::open(['method' => 'GET', 'route' => ['contract.note.destroy', $note->id]]) !!}
                                                                        <a href="#!"
                                                                            class="mx-3 btn btn-sm d-inline-flex align-items-center bs-pass-para btn-danger"
                                                                            data-bs-toggle="tooltip"
                                                                            data-bs-placement="top"
                                                                            title="{{ __('Delete Notes') }}">
                                                                            <span class="text-white"> <i
                                                                                    class="ti ti-trash"></i></span>
                                                                        </a>
                                                                        {!! Form::close() !!}
                                                                    </div>
                                                                @endif

                                                                @if (\Auth::user()->type != 'company' && $contract->edit_status == 'accept' && \Auth::user()->id == $note->created_by)
                                                                    <div class="col-auto">
                                                                        {!! Form::open(['method' => 'GET', 'route' => ['customer.contract.note.destroy', $note->id]]) !!}
                                                                        <a href="#!"
                                                                            class="mx-3 btn btn-sm d-inline-flex align-items-center bs-pass-para btn-danger"
                                                                            data-bs-toggle="tooltip"
                                                                            data-bs-placement="top"
                                                                            title="{{ __('Delete Notes') }}">
                                                                            <span class="text-white"> <i
                                                                                    class="ti ti-trash"></i></span>
                                                                        </a>
                                                                        {!! Form::close() !!}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
