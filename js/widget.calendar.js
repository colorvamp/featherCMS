var _widgetCalendar = {
	init: function(params){
		if(!params || !params.h || (params.h.firstChild && params.h.firstChild.className && params.h.firstChild.className == 'widgetCalendar')){return;}
		var widgetCalendar = $C('DIV',{className:'widgetCalendar',
			'monthOffset':0,
			'setCalendarOffset':function(offset){return _widgetCalendar.setCalendarOffset(widgetCalendar,offset);},
			'onDayClick':function(e,el,d,m,y){return _widgetCalendar.onDayClick(e,el,d,m,y);}
		},params.h);
		if(params && params.onDayClick){widgetCalendar.onDayClick = params.onDayClick;}
		 _widgetCalendar.setCalendarOffset(widgetCalendar,0);
	},
	setCalendarOffset: function(widgetCalendar,monthOffset){
		widgetCalendar.monthOffset = monthOffset;
		var now = new Date();var year = now.getFullYear();var month = now.getMonth()+monthOffset;
		while(month > 11){month-=12;year++;}while(month < 0){month+=12;year--;}

		var head = $C('TR',{},$C('TBODY',{},$C('TABLE',{className:'head','.width':'100%',cellPadding:0,cellSpacing:0},widgetCalendar.empty())));
		$C("TD",{className:'control',innerHTML:"&lt;",onclick:function(){_widgetCalendar.setCalendarOffset(widgetCalendar,widgetCalendar.monthOffset-12);}},head);
		$C("TD",{className:'year',innerHTML:year},head);
		$C("TD",{className:'control',innerHTML:"&gt;",onclick:function(){_widgetCalendar.setCalendarOffset(widgetCalendar,widgetCalendar.monthOffset+12);}},head);
		$C("TD",{className:'control',innerHTML:"&lt;",onclick:function(){_widgetCalendar.setCalendarOffset(widgetCalendar,widgetCalendar.monthOffset-1);}},head);
		$C("TD",{className:'month',innerHTML:_widgetCalendar.getMonthsNames()[month]},head);
		$C("TD",{className:'control',innerHTML:"&gt;",onclick:function(){_widgetCalendar.setCalendarOffset(widgetCalendar,widgetCalendar.monthOffset+1);}},head);

		var tbody = $C('TBODY',{},$C('TABLE',{className:'body','.width':'100%',cellPadding:0,cellSpacing:0},widgetCalendar));
		var tr = $C("TR",{".background":"#fad184"},tbody);
		$each(['','Lun','Mar','Mie','Jue','Vie','Sab','Dom'],function(k,elem){$C('TD',{className:'dayName',innerHTML:elem},tr);});

		var firstDay = new Date(year,month,1).getDay();if(firstDay==0){firstDay=7;}
		var monthDays = _widgetCalendar.getMonthDays(month+1,year);
		var numTrs = Math.ceil((monthDays+firstDay)/7);

		var m = month;
		var totalDays = new Date(year,0,1).getDay();if(totalDays==0){totalDays=7;}
		while(m>0){totalDays+=_widgetCalendar.getMonthDays(m+1,year);m--;}
		var initWeek = Math.ceil(totalDays/7);

		for(var row = 1;row <= numTrs;row++){
			var tr = $C("TR",{className:"widgetCalendarWeek"},tbody);
			$C('TD',{className:'weekNumber',innerHTML:initWeek},tr);initWeek++;
			for(var col=1;col<=7;col++){
				var day = (row*7)+col-firstDay-6;
				/* Si son los dias restantes del mes anterior */
				if(day<1){var td = $C('TD',{className:'emptyDay'},tr);continue;}
				/* Si son los dias del mes siguiente */
				if(day>monthDays){var td = $C('TD',{className:'emptyDay'},tr);continue;}
				$each([1],function(k,v){
					var td = $C('TD',{innerHTML:day,onclick:function(e){var d = td.getAttribute('data-day');var m = td.getAttribute('data-month');var y = td.getAttribute('data-year');widgetCalendar.onDayClick(e,td,y,m,d);}},tr);
					td.setAttribute('data-day',day);
					td.setAttribute('data-month',month);
					td.setAttribute('data-year',year);
				});
			}
		}
		return;
	},
	onDayClick: function(e,td,y,m,d){alert(y+'-'+m+'-'+d);},
	getMonthsNames: function(){return ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];},
	getMonthDays: function(m,a){var f = (((a % 4 == 0) && (a % 100 != 0)) || (a % 400 == 0)) ? 29 : 28;var months = [31,f,31,30,31,30,31,31,30,31,30,31];return months[m-1];}
};
