<div class="wrapper">
	<section>
		<h2><i class="icon-bar-chart"></i> Insights</h2>
		<p>Velocidad de carga</p>
		<div class="graph svg">
			{%html.track.graph%}
		</div>
		<div>
			<table class="table track">
				<thead>
					<tr>
						<th>url</th>
						<th class="min">num</th>
					</tr>
				</thead>
				<tbody>
					{%html.track.table.insight%}
				</tbody>
			</table>
		</div>
	</section>
</div>
