var featherStats = {
	vars: {'statsDay':false},
	indicateDay: function(el,day){
		if(!this.vars.statsDay || !this.vars.statsDay[day]){return;}
		while(el.parentNode && el.tagName != 'LI' && !el.className.match || !el.className.match(/block/)){el = el.parentNode;}if(!el.parentNode){return;}
		var anchor = el;

		var i = info_create('command',{'.width':'400px'},anchor);
		var h = i.infoContainer.empty();
		$C('H4',{innerHTML:'Estadísticas para el día '+day},h);
		$C('P',{innerHTML:'Visitas totales: '+this.vars.statsDay[day].count},h);
		var bh = $C('UL',{className:'buttonHolder right'},h);
		var b = gnomeButton_create('Cerrar',function(){info_destroy(h);},bh);
	}
};
