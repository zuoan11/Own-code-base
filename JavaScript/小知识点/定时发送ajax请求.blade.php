<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<title>Document</title>
</head>
<body>
	<h1>111</h1>
	<div id="main" style="width: 600px;height:400px;"></div>
	<script src="{{asset('admin/js/jq3.3.1.js')}}"></script>
	<script src="{{asset('admin/js/echarts.js')}}"></script>
	<script>
		
		// setInterval(function(){
		// 	// alert("Hello")
		// 	$.ajax({
		// 		type: 'get',
		// 		url: "{{url('admin/ajax')}}",
		// 		success: function(info) {
		// 			// alert(info);
		// 			xuanran(info);
		// 		},
		// 		error: function(info) {
		// 			alert('error');
		// 		}
		// 	});
		// }, 10000);

		// 基于准备好的dom，初始化echarts实例
        	var myChart = echarts.init(document.getElementById('main'));

	        var option = {
	            title: {
	                text: 'ECharts 入门示例'
	            },
	            tooltip: {},
	            legend: {
	                data:['销量']
	            },
	            xAxis: {
	                data: ["衬衫","羊毛衫","雪纺衫","裤子","高跟鞋","袜子"]
	            },
	            yAxis: {},
	            series: [{
	                name: '销量',
	                type: 'bar',
	                data: [50, 20, 36, 10, 10, 20]
	            }]
	        };

	        // 使用刚指定的配置项和数据显示图表。
	        myChart.setOption(option);

		// 渲染页面趋势图
		function xuanran (renyu) {
			// 基于准备好的dom，初始化echarts实例
        	var myChart = echarts.init(document.getElementById('main'));

	        var option = {
	            title: {
	                text: 'ECharts 入门示例'
	            },
	            tooltip: {},
	            legend: {
	                data:['销量']
	            },
	            xAxis: {
	                data: ["衬衫","羊毛衫","雪纺衫","裤子","高跟鞋","袜子"]
	            },
	            yAxis: {},
	            series: [{
	                name: '销量',
	                type: 'bar',
	                data: [renyu, 20, 36, 10, 10, 20]
	            }]
	        };

	        // 使用刚指定的配置项和数据显示图表。
	        myChart.setOption(option);
		}		


   
	</script>
</body>
</html>