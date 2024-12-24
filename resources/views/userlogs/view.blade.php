@php 
    $details = json_decode($details->details);
@endphp
<div class="row mx-3">
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('Status')}}</b></div>
        <p class="text-muted mb-4">
            {{$details->status}}
        </p>
    </div>
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('Country')}} </b></div>
        <p class="text-muted mb-4">
            {{$details->country}}
        </p>
    </div>
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('Country Code')}} </b></div>
        <p class="text-muted mb-4">
            {{$details->countryCode}}
        </p>
    </div>
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('Region')}}</b></div>
        <p class="mt-1">{{$details->region}}</p>
    </div>
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('Region Name')}}</b></div>
        <p class="mt-1">{{$details->regionName}}</p>
    </div>
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('City')}}</b></div>
        <p class="mt-1">{{$details->city}}</p>
    </div>
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('Zip')}}</b></div>
        <p class="mt-1">{{$details->zip}}</p>
    </div>
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('Latitude')}}</b></div>
        <p class="mt-1">{{$details->lat}}</p>
    </div>
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('Longitude')}}</b></div>
        <p class="mt-1">{{$details->lon}}</p>
    </div>
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('Timezone')}}</b></div>
        <p class="mt-1">{{$details->timezone}}</p>
    </div>
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('Isp')}}</b></div>
        <p class="mt-1">{{$details->isp}}</p>
    </div>
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('Org')}}</b></div>
        <p class="mt-1">{{$details->org}}</p>
    </div>
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('As')}}</b></div>
        <p class="mt-1">{{$details->as}}</p>
    </div>
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('Query')}}</b></div>
        <p class="mt-1">{{$details->query}}</p>
    </div>
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('Browser Name')}}</b></div>
        <p class="mt-1">{{$details->browser_name}}</p>
    </div>
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('Os Name')}}</b></div>
        <p class="mt-1">{{$details->os_name}}</p>
    </div>
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('Browser Language')}}</b></div>
        <p class="mt-1">{{$details->browser_language}}</p>
    </div>
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('Device Type')}}</b></div>
        <p class="mt-1">{{$details->device_type}}</p>
    </div>
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('Referrer Host')}}</b></div>
        <p class="mt-1">{{$details->referrer_host}}</p>
    </div>
    <div class="col-md-6 ">
        <div class="form-control-label"><b>{{__('Referrer Path')}}</b></div>
        <p class="mt-1">{{$details->referrer_path}}</p>
    </div>
</div>
