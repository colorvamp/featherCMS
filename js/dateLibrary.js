var _dateLibrary = {
	init: function(){},
	createInputDate: function(h,boxName,initDate,yearOffset){
		if(!yearOffset){yearOffset = 0;}var date = new Date();
		var selDay = $C("SELECT",{name:boxName+"_day"},h);
		var selMonth = $C("SELECT",{name:boxName+"_month",onchange:function(){renderSelDay();}},h);
		$A(_dateLibrary.getMonthsNames()).each(function(el,n){var val = n+1;val = ((val < 10) ? "0"+val : val);$C("OPTION",{value:val,innerHTML:val+". "+el},selMonth);});
		/*INI-year*/var selYear = $C("SELECT",{name:boxName+"_year",onchange:function(){renderSelDay();}},h);
		for(var a = date.getFullYear()+1+yearOffset;a > 1950;a--){$C("OPTION",{value:a,innerHTML:a},selYear);}
		selYear.value = date.getFullYear();/*FIN-year*/
		function renderSelDay(){
			var oldVal = selDay.value;var shouldValuate = false;$fix(selDay).empty();
			for(var a = 1;a < _dateLibrary.getMonthDays(selMonth.value,selYear.value)+1;a++){var val = ((a < 10) ? "0"+a : a);if(oldVal == val){shouldValuate = true;}$C("OPTION",{value:val,innerHTML:val},selDay);}
			if(shouldValuate){selDay.value = oldVal;}
		}
		renderSelDay();
		/*INI-initDate*/if(initDate){initDate = initDate.match(/^([0-9]{4})\-([0-9]{2})\-([0-9]{2})/);if(!initDate){return;}selDay.value = initDate[3];selMonth.value = initDate[2];selYear.value = initDate[1];}/*FIN-initDate*/
	},
	getMonthsNames: function(){return ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];},
	getNumberOfMonth: function(m){m = m[0].toUpperCase()+m.slice(1).toLowerCase();return this.getMonthsNames().indexOf(m)+1;},
	getMonthDays: function(m,a){var f = (((a % 4 == 0) && (a % 100 != 0)) || (a % 400 == 0)) ? 29 : 28;var months = [31,f,31,30,31,30,31,31,30,31,30,31];return months[m-1];},
	dateToObject: function(d){d = d.match(/([0-9]*)\-([0-9]*)\-([0-9]*)/);return {day:d[3].replace(/^0*/,""),monthNumber:d[2].replace(/^0*/,""),monthName:this.getMonthsNames()[d[2]-1],year:d[1]};},
	formatDateFriendly: function(d){d = d.match(/([0-9]*)\-([0-9]*)\-([0-9]*)/);return d[3]+" de "+this.getMonthsNames()[d[2]-1]+" de "+d[1];},
	formatLargeDateFriendly: function(d){d = d.match(/([0-9]*)\-([0-9]*)\-([0-9]*) ([0-9]*:[0-9]*)/);return d[3]+" de "+this.getMonthsNames()[d[2]-1]+" de "+d[1]+" a las "+d[4];},
	largeDateToObject: function(d){
		d = d.match(/([0-9]*)\-([0-9]*)\-([0-9]*) ([0-9]*):([0-9]*):([0-9]*)/);
		return {day:d[3].replace(/^0*/,""),monthNumber:d[2].replace(/^0*/,""),monthName:this.getMonthsNames()[d[2]-1],year:d[1],hour:d[4],minute:d[5],second:d[6]};
	}
};
