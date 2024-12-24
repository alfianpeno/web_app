{{Form::model(null, array('route' => array('features_update', $key), 'method' => 'POST','enctype' => "multipart/form-data", 'class'=>'needs-validation','novalidate'    )) }}
<div class="modal-body">
    @csrf
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('Heading', __('Heading'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::text('other_features_heading',$other_features['other_features_heading'], ['class' => 'form-control ', 'placeholder' => __('Enter Heading'), 'required'=>'required']) }}
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('Description', __('Description'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::textarea('other_featured_description', $other_features['other_featured_description'], ['class' => 'form-control summernote-simple', 'placeholder' => __('Enter Description'),'required'=>'required']) }}
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('Buy Now Link', __('Buy Now Link'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::text('other_feature_buy_now_link', $other_features['other_feature_buy_now_link'], ['class' => 'form-control', 'placeholder' => __('Enter Link'),'required'=>'required']) }}
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('Image', __('Image'), ['class' => 'form-label']) }}
                <input type="file" name="other_features_image" class="form-control" >
            </div>
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Update')}}" class="btn  btn-primary">
</div>
{{ Form::close() }}

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
