@php
    $chatGPT = \App\Models\Utility::settings('enable_chatgpt');
    $enable_chatgpt = !empty($chatGPT);
@endphp
{{ Form::open(['url' => 'coupons', 'method' => 'post', 'class'=>'needs-validation','novalidate' ]) }}
<div class="modal-body">
    <div class="row">
        @if ($enable_chatgpt)
            <div>
                <a href="#" data-size="md" data-ajax-popup-over="true" data-url="{{ route('generate', ['coupon']) }}"
                    data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('Generate') }}"
                    data-title="{{ __('Generate content with AI') }}" class="btn btn-primary btn-sm float-end">
                    <i class="fas fa-robot"></i>
                    {{ __('Generate with AI') }}
                </a>
            </div>
        @endif
        <div class="form-group col-md-12">
            {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::text('name', null, ['class' => 'form-control font-style', 'required' => 'required', 'placeholder' => __('Enter Name') ]) }}
        </div>

        <div class="form-group col-md-6">
            {{ Form::label('discount', __('Discount'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::number('discount', null, ['class' => 'form-control', 'required' => 'required', 'step' => '0.01','placeholder' => __('Enter Discount')]) }}
            <span class="small">{{ __('Note: Discount in Percentage') }}</span>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('limit', __('Limit'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::number('limit', null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('Enter Limit') ]) }}
        </div>


        <div class="form-group col-md-12">
            {{ Form::label('code', __('Code'), ['class' => 'form-label']) }}
            <div class="d-flex radio-check">
                <div class="form-check form-check-inline form-group col-md-6">
                    <input type="radio" id="manual_code" value="manual" name="icon-input"
                        class="form-check-input code" checked="checked">
                    <label class="custom-control-label " for="manual_code">{{ __('Manual') }}</label>
                </div>
                <div class="form-check form-check-inline form-group col-md-6">
                    <input type="radio" id="auto_code" value="auto" name="icon-input" class="form-check-input code">
                    <label class="custom-control-label" for="auto_code">{{ __('Auto Generate') }}</label>
                </div>
            </div>
        </div>

        <div class="form-group col-md-12 d-block" id="manual">
            <input class="form-control font-uppercase" name="manualCode" type="text" placeholder="{{__('Enter Manual Code')}}">
        </div>
        <div class="form-group col-md-12 d-none" id="auto">
            <div class="row">
                <div class="col-md-10">
                    <input class="form-control" name="autoCode" type="text" id="auto-code" placeholder="{{__('Enter Auto Generate Code')}}">
                </div>
                <div class="col-md-2">
                    <a href="#" class="btn btn-primary" id="code-generate"><i class="ti ti-history"></i></a>
                </div>
            </div>
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
</div>
{{ Form::close() }}
<script>
    $(document).on('click', '.code', function () {
        var type = $(this).val();
        if (type == 'manual') {
            $('#manual').removeClass('d-none');
            $('#manual').addClass('d-block');
            $('#auto').removeClass('d-block');
            $('#auto').addClass('d-none');
        } else {
            $('#auto').removeClass('d-none');
            $('#auto').addClass('d-block');
            $('#manual').removeClass('d-block');
            $('#manual').addClass('d-none');
        }
    });

    $(document).on('click', '#code-generate', function () {
        var length = 10;
        var result = '';
        var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        var charactersLength = characters.length;
        for (var i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * charactersLength));
        }
        $('#auto-code').val(result);
    });
    $(document).on('click', '#manual_code', function () {
        var result = null;
        $('#auto-code').val(result);
    });
    $(document).on('click', '#auto_code', function () {
        var result = null;
        $('#manual-code').val(result);
    });
</script>
