<form id="table_search" method="GET" action="{{ $route }}" accept-charset="UTF-8">
  <div class="row">
    @if($source == 'rest_api_token_content')  
    <div class="col-sm-4 form-group">
      <select class="form-control select2" style="width: 100%;" onchange="form.submit()" name="user">
        <option value="-1">{!! __('admin.search_content.default_option_label', ['attribute' => $search_lang['user']]) !!}</option>
        @if(count($users) > 0)
          @foreach($users as $user)
            @if(request('user') == $user['id'])
            <option selected="selected" value="{{ $user['id'] }}">{!! $user['name'] !!}</option>
            @else
            <option value="{{ $user['id'] }}">{!! $user['name'] !!}</option>
            @endif
          @endforeach
        @endif
      </select>
    </div>
    @endif
    
     @if($source == 'rest_api_token_content')
     <div class="col-sm-8 form-group">
     @elseif(($source == 'full_width') )  
     <div class="col-sm-12 form-group">
     @endif
      <div class="input-group">
        <input class="form-control" id="search" value="{{ request('search') }}" placeholder="{{ __('admin.search_content.search_placeholder', ['attribute' => $search_lang['placeholder_title']]) }}" name="search" type="text">
        <div class="input-group-btn">
          <button type="submit" class="btn btn-primary">
          {!! __('admin.search_content.search_btn_label') !!}
          </button>
        </div>
      </div>
    </div>
  </div>
</form>