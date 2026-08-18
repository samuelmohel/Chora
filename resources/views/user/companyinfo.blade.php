<div class="modal-body">
    <div class="row">
        <div class="row justify-content-center">
            <div class="col-sm-12 col-md-10 col-xxl-12 col-md-12">
                <div class="px-0 card-body">
                    <div class="tab-content" id="pills-tabContent">
                        @foreach ($users_data as $key => $user_data)
                            @php
                                $users = \App\Models\User::where('created_by', $id)->get();
                                $admin = \App\Models\User::find($id);
                            @endphp
                            <div class="tab-pane text-capitalize fade show {{ $loop->index == 0 ? 'active' : '' }}">
                                <div class="card">
                                    <div class="card-body p-3">
                                        <div class="row">
                                            <div class="col-4 text-center">
                                                <h6>{{ 'Total User' }}</h6>
                                                <p class=" text-sm mb-0">
                                                    <i
                                                        class="ti ti-users text-warning card-icon-text-space fs-5 mx-1"></i><span
                                                        class="total_workspace fs-5">
                                                        {{ $user_data['total_users'] }}</span>
                                                </p>
                                            </div>
                                            <div class="col-4 text-center">
                                                <h6>{{ 'Active User' }}</h6>
                                                <p class=" text-sm mb-0">
                                                    <i
                                                        class="ti ti-users text-primary card-icon-text-space fs-5 mx-1"></i><span
                                                        class="active_workspace fs-5">{{ $user_data['active_users'] }}</span>
                                                </p>
                                            </div>
                                            <div class="col-4 text-center">
                                                <h6>{{ 'Disable User' }}</h6>
                                                <p class=" text-sm mb-0">
                                                    <i
                                                        class="ti ti-users text-danger card-icon-text-space fs-5 mx-1"></i><span
                                                        class="disable_workspace fs-5">{{ $user_data['disable_users'] }}</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row my-2 " id="user_section">
                                    @if ($users->count() > 0)
                                        @foreach ($users as $user)
                                            <div class="col-md-6 my-2 ">
                                                <div
                                                    class="d-flex align-items-center justify-content-between list_colume_notifi pb-2">
                                                    <div class="mb-3 mb-sm-0">
                                                        <h6>
                                                            <img src="{{ !empty($user->avatar) ? asset(Storage::url($user->avatar)) : asset(Storage::url('uploads/avatar/avatar.png')) }}"
                                                                alt="image" class="wid-30 rounded-circle mx-2"
                                                                height="30">
                                                            <label for="user"
                                                                class="form-label">{{ $user->name }}</label>
                                                        </h6>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="form-check form-switch custom-switch-v1 mb-2">
                                                            <input type="checkbox" name="user_disable"
                                                                class="form-check-input input-primary is_disable"
                                                                value="1" data-id='{{ $user->id }}'
                                                                data-company="{{ $id }}"
                                                                data-name="{{ __('user') }}"
                                                                {{ $user->is_disable == 1 ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="user_disable"></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-center">
                                            <i class="fas fa-folder-open text-primary fs-40"></i>
                                            <h2>{{ __('Opps...') }}</h2>
                                            <h6> {!! __('No Data Found') !!} </h6>
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
<script>
    $(document).on("click", ".is_disable", function() {
        var id = $(this).attr('data-id');
        var name = $(this).attr('data-name');
        var company_id = $(this).attr('data-company');
        var is_disable = ($(this).is(':checked')) ? $(this).val() : 0;

        $.ajax({
            url: '{{ route('user.unable') }}',
            type: 'POST',
            data: {
                "is_disable": is_disable,
                "id": id,
                "name": name,
                "company_id": company_id,
                "_token": "{{ csrf_token() }}",
            },
            success: function(data) {
                if (data.success) {
                    if (name == 'owner') {
                        var container = document.getElementById('user_section');
                        var checkboxes = container.querySelectorAll('input[type="checkbox"]');
                        checkboxes.forEach(function(checkbox) {
                            if (is_disable == 0) {
                                checkbox.disabled = true;
                                checkbox.checked = false;
                            } else {
                                checkbox.disabled = false;
                            }
                        });

                    }

                    show_toastr('{{ __('success') }}', data.success, 'success');
                } else {
                    show_toastr('{{ __('error') }}', data.success, 'error');

                }

            }
        });
    });
</script>
