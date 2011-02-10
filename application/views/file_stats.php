<table class="tablegrid">
	<thead>
		<tr>
			<th style="width:10px;">#</th>
			<th style="width:400px;"><?=_("File")?></th>
			<th style="width:200px;"><?=_("Title")?></th>
			<th><?=_("Artist")?></th>
			<th><?=_("Album")?></th>
			<th style="width:40px;"><?=_("Play count")?></th>
		</tr>
	</thead>
	<tbody>
		<? $count = 1; foreach ($files as $file) { ?>
		<tr>
			<td class="center"><?=$count?></td>
			<td><?=$file['file_path']?></td>
			<td><?=$file['file_title']?></td>
			<td><?=$file['file_artist']?></td>
			<td><?=$file['file_album']?></td>
			<td class="center"><?=$file['file_playcount']?></td>
		</tr>
		<? $count++; } ?>
	</tbody>
</table>