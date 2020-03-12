@extends('layouts.admin.master')
@section('title', trans('admin.restapitoken.create_content.page_title') .' < '. get_site_title())

@section('content')
<form class="form-horizontal" method="POST" action="{{ route('rest-api-token.store') }}">
  @include('includes.csrf-token')
		
		<div class="box box-info">
    <div class="box-header">
      <h3 class="box-title">{!! trans('admin.restapitoken.create_content.header_title') !!}</h3>
      <a class="btn btn-default btn-sm back-to-list-link" href="{{ route('rest-api-token.index') }}" >{!! trans('admin.restapitoken.create_content.token_list_label') !!}</a>
      
      <div class="box-tools pull-right">
        <button class="btn btn-primary pull-right btn-sm" type="submit">{!! trans('admin.restapitoken.create_content.submit_btn_label') !!}</button>
      </div>
    </div>
  </div>
		
  @include('pages-message.notify-msg-error')
  @include('pages-message.form-submit')
  
		<div class="box box-solid">
				<div class="row">
						<div class="col-md-12">
								<div class="box-body">
										<div class="form-group">
            <div class="row">  
              <label class="col-sm-3 control-label" for="inputAPILabel">{!! trans('admin.restapitoken.create_content.field_title_1') !!}</label>
              <div class="col-sm-9">
                <input type="text" name="inputAPILabel" class="form-control">         
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="row">  
              <label class="col-sm-3 control-label" for="inputSelectUser">{!! trans('admin.restapitoken.create_content.field_title_2') !!}</label>
              <div class="col-sm-9">
                <select id="inputSelectUser" name="inputSelectUser" class="form-control select2" style="width: 100%;">
                  @if(count($users) > 0)
                    @foreach($users as $user)
                      <option value="{{ $user['id'] }}">{!! 'user' .'#'. $user['name'] .'__'. 'role#'. get_user_details($user['id'])['user_role'] !!}</option>
                    @endforeach
                  @endif
                </select>
              </div>
            </div>
          </div>  
          <div class="form-group">
            <div class="row">  
              <label class="col-sm-3 control-label" for="inputSelectUserLabel">{!! trans('admin.restapitoken.create_content.field_title_3') !!}</label>
              <div class="col-sm-9">
                <select id="rest_api_permissions_type" name="rest_api_permissions_type" class="form-control select2" style="width: 100%;">
                  <option value="read">{!! trans('admin.restapitoken.create_content.read_label') !!}</option>
                  <option value="write">{!! trans('admin.restapitoken.create_content.write_label') !!}</option>
                  <option value="read_write">{!! trans('admin.restapitoken.create_content.read_write_label') !!}</option>
                </select>
              </div>
            </div>
          </div>    
								</div>
						</div>
				</div>
		</div>		
</form>		
@endsection