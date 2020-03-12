<!DOCTYPE html>
<html>
  <head>
    <title>{!! trans('admin.not_installed_page_title') !!}</title>
    <link href="//fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">

    <style type="text/css">
      html, body {
        height: 100%;
      }

      body {
        margin: 0;
        padding: 0;
        width: 100%;
        color: #B0BEC5;
        display: table;
        font-weight: 100;
        font-family: 'Lato';
      }

      .container {
        text-align: center;
        display: table-cell;
        vertical-align: middle;
      }

      .content {
        text-align: center;
        display: inline-block;
      }

      .title {
        font-size:30px;
        margin-bottom: 40px;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="content">
        <div class="title">
          <div><h4>Please remove all database tables and try agin for install.</h4></div>
        </div>
      </div>
    </div>
  </body>
</html>