(function () {
    'use strict';
    var root = document.querySelector('[data-router-status]');
    if (!root) return;

    var interval = Math.max(3, Math.min(300, parseInt(root.dataset.pollSeconds || '3', 10))) * 1000;
    var busy = false, maxPoints = 40, charts = {};
    var select = function (name) { return root.querySelector('[data-status-' + name + ']'); };
    var bytes = function (value) {
        value = Number(value || 0); if (value <= 0) return '-';
        var units = ['B','KB','MB','GB','TB'], index = 0;
        while (value >= 1024 && index < units.length - 1) { value /= 1024; index++; }
        return value.toLocaleString('id-ID', {maximumFractionDigits:index ? 1 : 0}) + ' ' + units[index];
    };
    var usage = function (total, free) { total=Number(total||0);free=Number(free||0);return total>0?Math.max(0,Math.min(100,Math.round(((total-free)/total)*100))):0; };
    var animate = function (node) { if(!node)return;node.classList.remove('is-value-updated');void node.offsetWidth;node.classList.add('is-value-updated'); };
    var write = function (name, value) { var node=select(name);if(node&&node.textContent!==String(value)){node.textContent=value;animate(node);} };
    var uptime = function (value) {
        var parts={w:0,d:0,h:0,m:0,s:0},match,pattern=/(\d+)([wdhms])/g,text=String(value||'').toLowerCase();while((match=pattern.exec(text))!==null)parts[match[2]]=Number(match[1]);
        var seconds=parts.w*604800+parts.d*86400+parts.h*3600+parts.m*60+parts.s,short=[parts.w?parts.w+'W':'',parts.d?parts.d+'D':'',parts.h?parts.h+'H':'',parts.m?parts.m+'M':''].filter(Boolean).join(' ')||'0M';
        write('uptime-main',short);write('uptime-detail',parts.w+' minggu · '+parts.d+' hari · '+parts.h+' jam · '+parts.m+' menit');
        var since=seconds?new Date(Date.now()-seconds*1000).toLocaleString('id-ID',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}):'-';write('uptime-since',since);
    };
    var chartPalette = function () { var light=document.documentElement.dataset.appTheme==='light';return {line:light?'#078dc6':'#39c6ff',fill:light?'rgba(7,141,198,.16)':'rgba(57,198,255,.18)',grid:light?'rgba(44,100,135,.12)':'rgba(148,190,220,.09)',text:light?'#527087':'#93acc0'}; };
    var createChart = function (canvas) {
        if (typeof Chart === 'undefined') return null;
        var palette=chartPalette(),initial=Number(canvas.dataset.initialValue||0),now=new Date().toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
        return new Chart(canvas,{type:'line',data:{labels:[now],datasets:[{data:[initial],borderColor:palette.line,backgroundColor:'transparent',borderWidth:1.5,fill:false,tension:.28,pointRadius:0,pointHoverRadius:3,pointBackgroundColor:palette.line,pointBorderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,animation:{duration:280,easing:'easeOutQuart'},layout:{padding:{top:5,right:5,bottom:0,left:0}},interaction:{mode:'nearest',intersect:false},plugins:{legend:{display:false},tooltip:{displayColors:false,callbacks:{title:function(items){return items[0] ? items[0].label : '';},label:function(context){return ' '+context.parsed.y+'%';}}}},scales:{x:{display:true,border:{display:false},grid:{display:false},ticks:{color:palette.text,font:{size:8},maxTicksLimit:4,maxRotation:0,minRotation:0,autoSkip:true}},y:{display:true,min:0,max:100,border:{display:false},ticks:{display:true,color:palette.text,font:{size:8},stepSize:25,padding:4,callback:function(value){return value+'%';}},grid:{color:palette.grid,drawTicks:false,lineWidth:1}}}}});
    };
    root.querySelectorAll('[data-history-chart]').forEach(function(canvas){charts[canvas.dataset.historyChart]=createChart(canvas);});
    var history = function (name, value) {
        var chart=charts[name];if(!chart)return;value=Math.max(0,Math.min(100,Number(value||0)));
        chart.data.labels.push(new Date().toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit'}));chart.data.datasets[0].data.push(value);
        if(chart.data.labels.length>maxPoints){chart.data.labels.shift();chart.data.datasets[0].data.shift();}chart.update();
    };
    var state = function (online) {
        var node=select('state'),error=select('error');if(!node)return;var changed=node.classList.contains('is-error')===online;node.classList.toggle('is-error',!online);
        var icon=node.querySelector('i'),label=node.querySelector('span');if(icon)icon.className='fa-solid '+(online?'fa-circle-check':'fa-circle-xmark');if(label)label.textContent=online?'Terhubung · realtime':'Koneksi terputus';if(error&&online)error.hidden=true;if(changed)animate(node);
    };
    var poll = function () {
        if(busy||document.hidden)return;busy=true;var syncIcon=select('sync');if(syncIcon)syncIcon.classList.add('fa-spin');
        fetch(root.dataset.statusUrl,{headers:{'X-Requested-With':'XMLHttpRequest'},cache:'no-store',loader:false})
            .then(function(response){return response.json().then(function(json){if(!response.ok||!json.success)throw new Error(json.message||'Gagal mengambil status.');return json;});})
            .then(function(json){
                var data=json.data||{},resource=data.resource||{},health=data.health||{};
                var cpu=Math.max(0,Math.min(100,Number(resource['cpu-load']||0))),memory=usage(resource['total-memory'],resource['free-memory']),storage=usage(resource['total-hdd-space'],resource['free-hdd-space']);
                write('cpu',cpu+'%');history('cpu',cpu);
                write('memory',memory+'% · '+bytes(Number(resource['total-memory']||0)-Number(resource['free-memory']||0)));history('memory',memory);
                write('storage',storage+'% · '+bytes(Number(resource['total-hdd-space']||0)-Number(resource['free-hdd-space']||0)));history('storage',storage);
                uptime(resource.uptime||'');write('interfaces',Number(data.running_interfaces||0)+' / '+Number(data.enabled_interfaces||0));write('sessions',Number(data.active_sessions||0)+' sesi');
                var temperature=health.temperature!==undefined?health.temperature:health['cpu-temperature'];write('temperature',temperature!==undefined&&temperature!==''?temperature+(isNaN(Number(temperature))?'':' °C'):'-');state(true);
            }).catch(function(){state(false);}).finally(function(){busy=false;if(syncIcon)syncIcon.classList.remove('fa-spin');});
    };
    window.setInterval(poll,interval);
    document.addEventListener('visibilitychange',function(){if(!document.hidden)poll();});
    window.addEventListener('appthemechange',function(){var palette=chartPalette();Object.keys(charts).forEach(function(key){var chart=charts[key];if(!chart)return;chart.data.datasets[0].borderColor=palette.line;chart.data.datasets[0].backgroundColor=palette.fill;chart.data.datasets[0].pointBackgroundColor=palette.line;chart.options.scales.y.grid.color=palette.grid;chart.update('none');});});
}());
