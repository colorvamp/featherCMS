<div class="wrapper">
	<section>
		<h2>Visitas del blog</h2>
		<p>Grafico de la evolución de visitantes del blog. Este gráfico representa las visitas totales, es decir, no se filtran visitas consecutivas realizadas por el mismo usuario. Si un usuario realizase dos visitas a cualquiera de las páginas del blog, esas dos visitas quedarán aquí reflejadas.</p>
		<div>Visitas totales: {%html.track.visits.total%}</div>
		<div>Visitas únicas: {%html.track.visits.unique%}</div>
		<div class="graph svg">
			{%html.track.graph%}
		</div>
		<div>
			<table class="table track">
				<thead>
					<tr>
						<th>url</th>
						<th class="min">time</th>
					</tr>
				</thead>
				<tbody>
					{%html.track.table.rank%}
				</tbody>
			</table>
		</div>
	</section>
</div>
