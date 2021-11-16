@if ($offset)
<a class="btn btn-primary pull-right" style="margin-left:10px;" href="{{ admin_route('dcat-log-viewer.index', compact('file', 'offset')) }}">{{ $content }}</a>
@else
<button class="btn btn-primary pull-right" style="margin-left:10px;" disabled>{{ $content }}</button>
@endif

