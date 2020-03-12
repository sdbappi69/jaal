@extends('layouts.admin.master')
@section('title', __('admin.restapitoken.list_content.page_title') .' < '. get_site_title())

@section('content')
@include('pages-message.notify-msg-success')

<div class="row">
  <div class="col-6">
    <h5>{!! __('admin.restapitoken.list_content.header_title') !!}</h5>
  </div>
  <div class="col-6">
    <div class="pull-right">
      <a href="{{ route('rest-api-token.create') }}" class="btn btn-primary pull-right btn-sm">{!! __('admin.restapitoken.list_content.create_link_label') !!}</a>
    </div>  
  </div>
</div>
<br>
@include('includes.search', array('source' => 'rest_api_token_content', 'users' => $users, 'route' => route('rest-api-token.index'), 'search_lang' => array('user' => __('admin.restapitoken.search_content.user_label'), 'placeholder_title' => __('admin.restapitoken.search_content.title'))))
<div class="row">
  <div class="col-12">
    <div class="box">
      <div class="box-body">
        <table class="table table-bordered admin-data-table admin-data-list">
          <thead class="thead-dark">
            <tr>
              <th>{!! __('admin.restapitoken.list_content.table_title_1') !!}</th>
              <th>{!! __('admin.restapitoken.list_content.table_title_2') !!}</th>
              <th>{!! __('admin.restapitoken.list_content.table_title_3') !!}</th>
              <th>{!! __('admin.restapitoken.list_content.table_title_4') !!}</th>
              <th>{!! __('admin.restapitoken.list_content.table_title_5') !!}</th>
            </tr>
          </thead>
          <tbody>
            @if(count($tokens['tokens'])>0)
              @foreach($tokens['tokens'] as $token)
                <tr>
                  <td>{!! $token->title !!}</td>
                  <td>{!! 'user' .'#'. get_user_details($token->user_id)['user_name'] .'__'. 'role#'. get_user_details($token->user_id)['user_role'] !!}</td>
                  <td>
                    @if($token->permissions == 'read')
                      {!! __('admin.restapitoken.list_content.read_label') !!}
                    @elseif($token->permissions == 'write')
                      {!! __('admin.restapitoken.list_content.write_label') !!}
                    @else
                      {!! __('admin.restapitoken.list_content.read_write_label') !!}
                    @endif
                  </td>
                  <td>{!! Carbon\Carbon::parse( $token->created_at )->format('F d, Y') !!}</td>
                  <td>
                    <form action="{{ route('rest-api-token.destroy', $token->id) }}" method="POST">  
                      <a class="btn btn-info openTokenModal" data-token="{{ $token->token }}" href="" >{!! __('admin.restapitoken.list_content.get_token_label') !!}</a>  
                      <a class="btn btn-primary" href="{{ route('rest-api-token.edit', $token->id) }}">{!! __('admin.edit') !!}</a>
                      @include('includes.csrf-token')
                      @method('DELETE')
                      <button type="submit" class="btn btn-danger">{!! __('admin.delete') !!}</button>
                    </form>    
                  </td>
                </tr>
              @endforeach
            @else
            <tr><td colspan="5"><i class="fa fa-exclamation-triangle"></i> {!! __('admin.table_empty.no_data_found_label') !!}</td></tr>
            @endif
          </tbody>
          <tfoot class="thead-dark">
            <th>{!! __('admin.restapitoken.list_content.table_title_1') !!}</th>
            <th>{!! __('admin.restapitoken.list_content.table_title_2') !!}</th>
            <th>{!! __('admin.restapitoken.list_content.table_title_3') !!}</th>
            <th>{!! __('admin.restapitoken.list_content.table_title_4') !!}</th>
            <th>{!! __('admin.restapitoken.list_content.table_title_5') !!}</th>
          </tfoot>
        </table>
        <br>
        <div class="products-pagination">{!! $tokens['tokens']->links() !!}</div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="getTokenModal" tabindex="-1" role="dialog" aria-labelledby="updater" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <p class="no-margin">{!! __('admin.restapitoken.list_content.token_modal_title') !!}</p>
      </div>   
      <div class="modal-body">             
        <input type="text" readonly="true" id="token_input" class="form-control">
      </div>
      <div class="modal-footer">
          <button type="button" class="btn btn-default attachtopost" data-dismiss="modal">{!! __('admin.close') !!}</button>
      </div>
    </div>
  </div>
</div>
@endsection