<?php
return [
  'middleware' => [
    'token_required' => 'Bad request, apitoken header missing',
    'unauthorized' => 'Unauthorized, wrong token',
    'forbidden' => 'You have no :attribute permission',
    'bad_parameter' => 'Bad parameter request',
    'created_successfully' => 'You have successfully :attribute created',
    'updated_successfully' => 'You have successfully :attribute updated',
    'delete_successfully' => 'You have successfully :attribute deleted',
    'bad_request' => 'Bad request'
  ],

  'field_validation' => [
    'products' => [
      'title_field_required_msg' => 'Title field required',
      'type_field_required_msg' => 'Type field required',
      'status_field_required_msg' => 'Status field required'
    ],

    'coupon' => [
      'code_field_required_msg' => 'Code field required',
      'type_field_required_msg' => 'Type field required',
      'status_field_required_msg' => 'Status field required',
      'end_date_field_required_msg' => 'End date field required',
      'amount_field_required_msg' => 'Amount field required'
    ],
    
    'customers' => [
      'display_name_field_required_msg' => 'Display name is required',
      'user_name_field_required_msg' => 'User name is required',
      'email_field_required_msg' => 'Email is required',
      'status_field_required_msg' => 'Status is required',
      'secret_key_field_required_msg' => 'Secret key is required'
    ]
  ]
];