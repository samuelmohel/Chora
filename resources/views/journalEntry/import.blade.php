{{ Form::open(['route' => ['journal.import'], 'method' => 'post', 'enctype' => 'multipart/form-data', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-12">
            {{ Form::label('add_to_journal', 'Select Journal To Add Upload To', ['class' => 'form-label']) }}<x-required></x-required>
            <div class="form-group">
                <label style="width: 100%" for="add_to_journal" class="form-label">
                    <select class="form-control" name="add_to_journal_id" id="add_to_journal">
                        @foreach($journals as $journal)
                            @if(isset($journal['name']))
                                <option value="">{{ $journal['name'] }}</option>
                            @else
                                <option value="{{$journal['id']}}">JUR{{ sprintf('%05d', $journal['journal_id']) }}</option>
                            @endif
                        @endforeach
                    </select>
                </label>
            </div>
        </div>
        <div class="col-12">
            {{ Form::label('credit_account', 'Select Account To Credit', ['class' => 'form-label']) }}<x-required></x-required>
            <div class="form-group">
                <label style="width: 100%" for="credit_account" class="form-label">
                    <select class="form-control" name="credit_account" id="credit_account">
                        @foreach($accounts as $account)
                            <option value="{{$account['id']}}">{{ $account['name'] }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </div>
        <div class="col-12">
            {{ Form::label('debit_account', 'Select Account To Debit', ['class' => 'form-label']) }}<x-required></x-required>
            <div class="form-group">
                <label style="width: 100%" for="debit_account" class="form-label">
                    <select class="form-control" name="debit_account" id="debit_account">
                        @foreach($accounts as $account)
                            <option value="{{$account['id']}}">{{ $account['name'] }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </div>
        <div class="col-12">
            {{ Form::label('reference', 'Enter Journal Reference', ['class' => 'form-label']) }}
            <div class="form-group">
                <label style="width: 100%" for="reference" class="form-label">
                    <input style="width: 100%" type="text" class="form-control" name="reference" id="reference">
                </label>
            </div>
        </div>
        <div class="col-12">
            {{ Form::label('description', 'Enter Journal Description', ['class' => 'form-label']) }}
            <div class="form-group">
                <label style="width: 100%" for="description" class="form-label">
                    <textarea style="width: 100%" class="form-control" name="description" id="description" />
                </label>
            </div>
        </div>
        <div class="col-md-12">
            {{ Form::label('file', __('Select CSV File'), ['class' => 'form-label']) }}<x-required></x-required>
            <div class="choose-file form-group">
                <label for="file" class="form-label">
                    <input type="file" class="form-control" name="file" id="file" data-filename="upload_file"
                        required>
                </label>
                <p class="upload_file"></p>
            </div>
        </div>
        <div class="col-md-12 mb-6">
            {{ Form::label('file', __('Download sample journal CSV file'), ['class' => 'form-label']) }}
            <a href="{{ asset(Storage::url('uploads/sample')) . '/sample-journal.csv' }}" class="btn btn-sm btn-primary">
                <i class="ti ti-download"></i> {{ __('Download') }}
            </a>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Upload') }}" class="btn  btn-primary">
</div>
{{ Form::close() }}
