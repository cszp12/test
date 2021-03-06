/**
 * php strtr 2个参数版
 * @param  {string} str   
 * @param  {object} assoc 
 * @return {string}       
 */
function strtr(str, assoc){
	for(var k in assoc){
		str = str.replace(new RegExp(k, 'g'), assoc[k]);
	}

	return str;
}

/**
 * php substr_count的js版
 * @param  {string} str    
 * @param  {string} search 
 * @param  {int} total  不需要传该参数
 * @return {int}        
 */
function substr_count(str, search, total){
    var total = total || 0,
        index = str.indexOf(search);

    if(index === -1) return total;

    return substr_count(str.substr(++index), search, ++total);
}

/**
 * 返回2015-11:03 11:20
 * @param  {object} obj new Date()的对象
 * @return {string}     
 */
function getDate(obj){
	var day = obj.getDate().toString();
	if(day.length == 1) day = '0' + day;

	return obj.getFullYear() + '-' + obj.getMonth() + '-' + day + ' ' + obj.getHours() + ':' + obj.getMinutes();
}
