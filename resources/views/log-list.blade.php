<div class="dcat-log-viewer-log-list">
	<div class="list-group">
		@foreach ($list as $item)
		<a href="{{ admin_route('dcat-log-viewer.index', ['file'=>$item]) }}" class="list-group-item list-group-item-action {{ request('file') == $item || ($loop->first && !request('file')) ? 'active' : '' }}">{{ $item }}</a>
		@endforeach
	</div>
</div>
