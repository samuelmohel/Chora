@extends('layouts.admin')
@section('page-title')
    {{__('Manage Chart of Account Type')}}
@endsection

@section('action-btn')
<div class="float-end">
    
            <a href="#" data-url="{{ route('chart-of-account-type.create') }}" data-bs-toggle="tooltip" title="{{__('Create')}}" data-size="md" data-ajax-popup="true" data-title="{{__('Create New Account')}}" class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
</div>
@endsection
@section('content')
    <div class="row">
            <div class="col-md-12">
                <div class="card">
                
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table datatable">
                                <thead>
                                <tr>
                                    <th> {{__('Name')}}</th>
                                    <th width="10%"> {{__('Action')}}</th>
                                </tr>
                                </thead>
                                <tbody>
                                    @foreach ($types as $type)
                                    <tr>
                                        <td>{{ $type->name }}</td>
                                        <td class="Action">
                                            <span>
                                                {{-- @can('edit constant chart of account type') --}}
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center" data-url="{{ route('chart-of-account-type.edit',$type->id) }}" data-ajax-popup="true" data-title="{{__('Edit Coupon')}}" data-bs-toggle="tooltip" title="{{__('Edit')}}"  data-original-title="{{__('Edit')}}">
                                                        <i class="ti ti-pencil text-white"></i>
                                                    </a>
                                                </div>
                                                    {{-- <a href="#" class="mx-3 btn btn-sm align-items-center" data-url="{{ route('chart-of-account-type.edit',$type->id) }}" data-ajax-popup="true" data-title="{{__('Edit Unit')}}" data-bs-toggle="tooltip" data-original-title="{{__('Edit')}}">
                                                    <i class="ti ti-pencil text-white"></i> --}}
                                                {{-- </a> --}}
                                                {{-- @endcan --}}
                                                {{-- @can('delete constant chart of account type') --}}

                                                <div class="action-btn bg-danger ms-2">
                                                    {!! Form::open(['method' => 'DELETE', 'route' => ['chart-of-account-type.destroy', $type->id],'id'=>'delete-form-'.$type->id]) !!}
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{__('Delete')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$type->id}}').submit();">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </a>
                                                    {!! Form::close() !!}
                                                </div>


                                                    {{-- <a href="#" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$type->id}}').submit();">
                                                    <i class="ti ti-trash text-white"></i>
                                                </a>
                                                    {!! Form::open(['method' => 'DELETE', 'route' => ['chart-of-account-type.destroy', $type->id],'id'=>'delete-form-'.$type->id]) !!}
                                                    {!! Form::close() !!} --}}
                                                {{-- @endcan --}}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        
    </div>


@endsection
