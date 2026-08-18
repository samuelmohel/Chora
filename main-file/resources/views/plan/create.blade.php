@php
    $setting = App\Models\Utility::settings();
    $chatGPT = \App\Models\Utility::settings('enable_chatgpt');
    $enable_chatgpt = !empty($chatGPT);
@endphp
{{ Form::open(['url' => 'plans', 'enctype' => 'multipart/form-data','class'=>'needs-validation','novalidate']) }}
<div class="modal-body">
    <div class="row">
        @if ($enable_chatgpt)
            <div>
                <a href="#" data-size="md" data-ajax-popup-over="true" data-url="{{ route('generate', ['plan']) }}"
                    data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('Generate') }}"
                    data-title="{{ __('Generate content with AI') }}" class="btn btn-primary btn-sm float-end">
                    <i class="fas fa-robot"></i>
                    {{ __('Generate with AI') }}
                </a>
            </div>
        @endif
        <div class="form-group col-md-12">
            {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::text('name', null, ['class' => 'form-control font-style', 'placeholder' => __('Enter Plan Name'), 'required' => 'required']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('price', __('Price'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::number('price', null, ['class' => 'form-control', 'placeholder' => __('Enter Plan Price'), 'required' => 'required', 'step' => '0.01']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('duration', __('Duration'), ['class' => 'form-label']) }}<x-required></x-required>
            {!! Form::select('duration', $arrDuration, null, ['class' => 'form-control select', 'required' => 'required']) !!}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('max_users', __('Maximum Users'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::number('max_users', null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('Enter Maximum Users')]) }}
            <span class="small">{{ __('Note: "-1" for Unlimited') }}</span>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('max_customers', __('Maximum Customers'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::number('max_customers', null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('Enter Maximum Customers')]) }}
            <span class="small">{{ __('Note: "-1" for Unlimited') }}</span>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('max_venders', __('Maximum Venders'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::number('max_venders', null, ['class' => 'form-control', 'required' => 'required','placeholder' => __('Enter Maximum Venders')]) }}
            <span class="small">{{ __('Note: "-1" for Unlimited') }}</span>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('storage_limit', __('Storage Limit'), ['class' => 'form-label']) }}<x-required></x-required>
            <div class="input-group search-form">
                <input type="number" name="storage_limit" value="" class="form-control"  placeholder = "{{__('Enter Storage Limit') }}" required>
                <span class="input-group-text bg-transparent">{{ __('MB') }}</span>
                {{-- <span class="small">{{ __('Note: upload size ( In MB)') }}</span> --}}
            </div>
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
            {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => '3', 'placeholder' => __('Enter Description')]) !!}
        </div>
        <div class="row">
            <div class="col-md-6 mt-3 plan_price_div">
                <label class="form-check-label" for="trial"></label>
                <div class="form-group">
                    <label for="trial" class="form-label">{{ __('Trial is enable(on/off)') }}</label>
                    <div class="form-check form-switch custom-switch-v1 float-end">
                        <input type="checkbox" name="trial" class="form-check-input input-primary pointer" value="1" id="trial">
                        <label class="form-check-label" for="trial"></label>
                    </div>
                </div>
            </div>
            <div class="col-md-6 d-none plan_div plan_price_div">
                <div class="form-group">
                    {{ Form::label('trial_days', __('Trial Days'), ['class' => 'form-label']) }}
                    {{ Form::number('trial_days',null, ['class' => 'form-control','placeholder' => __('Enter Trial days'),'step' => '1','min'=>'1']) }}
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="custom-control form-switch pt-2">
                <input type="checkbox" class="form-check-input" name="enable_chatgpt" id="enable_chatgpt">
                <label class="custom-control-label form-check-label" for="enable_chatgpt">{{ __('Chat GPT') }}</label>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
</div>
{{ Form::close() }}

{{-- @push('script-page') --}}
<script>
    $(document).on('change', '#is_free_plan', function() {
        var value =  $(this).val();
        PlanLable(value);
    });
    $(document).on('change', '#trial', function() {
        if ($(this).is(':checked')) {
            $('.plan_div').removeClass('d-none');
            $('#trial').attr("required", true);

        } else {
            $('.plan_div').addClass('d-none');
            $('#trial').removeAttr("required");
        }
    });

    $(document).on('keyup mouseup', '#number_of_user', function() {
        var user_counter = parseInt($(this).val());
        if (user_counter == 0  || user_counter < -1)
        {
            $(this).val(1)
        }

    });
    $(document).on('keyup mouseup', '#number_of_workspace', function() {
        var workspace_counter = parseInt($(this).val());
        if (workspace_counter == 0 || workspace_counter < -1)
        {
            $(this).val(1)
        }
    });

    function PlanLable(value){
        if(value == 1){
            $('.plan_price_div').addClass('d-none');
        }
        if(value == 0){
            $('.plan_price_div').removeClass('d-none');
            if ($(".add_lable").find(".text-danger").length === 0) {
                $(".add_lable").append(`<span class="text-danger"> <sup>Paid</sup></span>`);
            }
        }
    }
</script>
{{-- @endpush --}}
