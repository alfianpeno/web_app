<div class="modal-body">
    <div class="card">
        <div class="card-body table-border-style">
            <div class="table-responsive">
                <table class="table datatable">
                    @foreach($plans as $plan)
                        <tr>
                            <td><h6>{{$plan->name}} ({{(env('CURRENCY_SYMBOL')) ? env('CURRENCY_SYMBOL') : '$'}}{{$plan->price}}) {{' / '. $plan->duration}}</h6></td>
                            <td>{{__('Users')}} : {{$plan->max_users}}</td>
                            <td>{{__('Customers')}} : {{$plan->max_customers}}</td>
                            <td>{{__('Vendors')}} : {{$plan->max_venders}}</td>
                            <td>
                                @if($user->plan==$plan->id)
                                    <span class="btn badge-success btn-xs rounded-pill my-auto"><i class="ti ti-check text-white"></i></span>
                                @else
                                    <a href="{{route('plan.active',[$user->id,$plan->id])}}" class="btn badge-blue btn-xs rounded-pill my-auto text-white" title="{{__('Click to Upgrade Plan')}}"><i class="ti ti-cart-plus"></i></a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>
