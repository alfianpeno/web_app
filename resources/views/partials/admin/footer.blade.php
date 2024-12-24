@php
use App\Models\Utility;
$setting_arr = Utility::file_validate();
@endphp



<!-- [ Main Content ] end -->
<footer class="dash-footer">
    <div class="footer-wrapper">
        <div class="py-1">
            <span class="text-muted">
                {{__('©') }} {{ date('Y') }} {{ Utility::getValByName('footer_text') ? Utility::getValByName('footer_text') : config('app.name', 'WorkGo') }}
            </span>
        </div>
        <div class="py-1">
    {{--            <ul class="list-inline m-0">--}}
    {{--                <li class="list-inline-item">--}}
    {{--                    <a class="link-secondary" href="javascript:">Joseph William</a>--}}
    {{--                </li>--}}
    {{--                <li class="list-inline-item">--}}
    {{--                    <a class="link-secondary" href="javascript:">About Us</a>--}}
    {{--                </li>--}}
    {{--                <li class="list-inline-item">--}}
    {{--                    <a class="link-secondary" href="javascript:">Blog</a>--}}
    {{--                </li>--}}
    {{--                <li class="list-inline-item">--}}
    {{--                    <a class="link-secondary" href="javascript:">Library</a>--}}
    {{--                </li>--}}
    {{--            </ul>--}}
        </div>
    </div>
</footer>



<!-- Warning Section Ends -->
<!-- Required Js -->
<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src="{{ asset('js/jquery.form.js') }}"></script>
<script src="{{ asset('assets/js/plugins/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/perfect-scrollbar.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/feather.min.js') }}"></script>
<script src="{{ asset('assets/js/dash.js') }}"></script>

<script src="{{asset('assets/js/plugins/datepicker-full.min.js')}}"></script>
<script src="{{asset('assets/js/plugins/dropzone-amd-module.min.js')}}"></script>

<script src="{{ asset('assets/js/plugins/choices.min.js') }}"></script>

<script src="{{ asset('assets/js/plugins/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/simple-datatables.js') }}"></script>

<!-- sweet alert Js -->
{{--<script src="{{ asset('assets/js/plugins/sweetalert.min.js') }}"></script>--}}


<!--Botstrap switch-->
<script src="{{ asset('assets/js/plugins/bootstrap-switch-button.min.js') }}"></script>


<!-- Apex Chart -->
<script src="{{ asset('assets/js/plugins/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/main.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/flatpickr.min.js') }}"></script>

<script>
    var file_size = "{{ $setting_arr['max_size'] }}";
    var file_types = "{{ $setting_arr['types'] }}";
    var type_err = "{{ __('Invalid file type. Please select a valid file ') }}" + "({{ $setting_arr['types'] }}).";
    var size_err = "{{ __('File size exceeds the maximum limit of ') }}" + " {{ $setting_arr['max_size'] / 1024 }} MB.";
</script>

<script src="{{ asset('js/custom.js') }}"></script>

<script src="{{ asset('assets/js/plugins/simplebar.min.js') }}"></script>

@if($message = Session::get('success'))
    <script>
        show_toastr('success', '{!! $message !!}');
    </script>
@endif
@if($message = Session::get('error'))
    <script>
        show_toastr('error', '{!! $message !!}');
    </script>
@endif
@stack('script-page')



<script>

    feather.replace();

    function removeClassByPrefix(node, prefix) {
        for (let i = 0; i < node.classList.length; i++) {
            let value = node.classList[i];
            if (value.startsWith(prefix)) {
                node.classList.remove(value);
            }
        }
    }
</script>
