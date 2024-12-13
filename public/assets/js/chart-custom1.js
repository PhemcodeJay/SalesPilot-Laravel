(function(jQuery) {

    "use strict";

// for apexchart
function apexChartUpdate(chart, detail) {
    let color = getComputedStyle(document.documentElement).getPropertyValue('--dark');
    if (detail.dark) {
      color = getComputedStyle(document.documentElement).getPropertyValue('--white');
    }
    chart.updateOptions({
      chart: {
        foreColor: color
      }
    })
  }
  
// for am chart
function amChartUpdate(chart, detail) {
  // let color = getComputedStyle(document.documentElement).getPropertyValue('--dark');
  if (detail.dark) {
    // color = getComputedStyle(document.documentElement).getPropertyValue('--white');
    chart.stroke = am4core.color(getComputedStyle(document.documentElement).getPropertyValue('--white'));
  }
  chart.validateData();
}

/*---------------------------------------------------------------------
   Apex Charts
-----------------------------------------------------------------------*/
if (jQuery("#apex-basic").length) {
  $.ajax({
    url: 'chart-data.php', // Replace with the actual path to your PHP script
    method: 'GET',
    dataType: 'json',
    success: function(response) {
      const salesData = response.apexBasicChart.map(item => item.total_sales);
      const salesDates = response.apexBasicChart.map(item => item.date);

      const options = {
        chart: {
          height: 350,
          type: "line",
          zoom: {
            enabled: false
          }
        },
        colors: ["#4788ff"],
        series: [{
          name: "Sales Quantity",
          data: salesData
        }],
        dataLabels: {
          enabled: false
        },
        stroke: {
          curve: "straight"
        },
        title: {
          text: "Sales Trends by Date",
          align: "left"
        },
        grid: {
          row: {
            colors: ["#f3f3f3", "transparent"],
            opacity: 0.5
          }
        },
        xaxis: {
          categories: salesDates
        }
      };

      if (typeof ApexCharts !== typeof undefined) {
        const chart = new ApexCharts(document.querySelector("#apex-basic"), options);
        chart.render();

        const body = document.querySelector('body');
        if (body.classList.contains('dark')) {
          apexChartUpdate(chart, {
            dark: true
          });
        }

        document.addEventListener('ChangeColorMode', function(e) {
          apexChartUpdate(chart, e.detail);
        });
      }
    },
    error: function(error) {
      console.error("Error fetching sales data: ", error);
    }
  });
}

if (jQuery("#apex-line-area").length) {
  $.ajax({
    url: 'chart-data.php', // Replace with the actual path to your PHP script
    method: 'GET',
    dataType: 'json',
    success: function(response) {
      const sellThroughData = response.apexLineAreaChart.map(item => item.avg_sell_through_rate);
      const inventoryTurnoverData = response.apexLineAreaChart.map(item => item.avg_inventory_turnover_rate);
      const dates = response.apexLineAreaChart.map(item => item.date);

      const options = {
        chart: {
          height: 350,
          type: "area"
        },
        dataLabels: {
          enabled: false
        },
        stroke: {
          curve: "smooth"
        },
        colors: ["#4788ff", "#ff4b4b"],
        series: [{
          name: "Sell-Through Rate",
          data: sellThroughData
        }, {
          name: "Inventory Turnover Rate",
          data: inventoryTurnoverData
        }],
        xaxis: {
          type: "datetime",
          categories: dates
        },
        tooltip: {
          x: {
            format: "dd/MM/yy"
          }
        }
      };

      const chart = new ApexCharts(document.querySelector("#apex-line-area"), options);
      chart.render();

      const body = document.querySelector('body');
      if (body.classList.contains('dark')) {
        apexChartUpdate(chart, {
          dark: true
        });
      }

      document.addEventListener('ChangeColorMode', function(e) {
        apexChartUpdate(chart, e.detail);
      });
    },
    error: function(error) {
      console.error("Error fetching sell-through rate and inventory turnover data: ", error);
    }
  });
}


if (jQuery("#apex-column").length) {
  $.ajax({
    url: 'chart-data.php', // Replace with the actual path to your PHP script
    method: 'GET',
    dataType: 'json',
    success: function(response) {
      const revenueData = response.apex3ColumnChart.map(item => item.revenue);
      const totalExpensesData = response.apex3ColumnChart.map(item => item.total_expenses);
      const profitData = response.apex3ColumnChart.map(item => item.profit);
      const dates = response.apex3ColumnChart.map(item => item.date);

      const options = {
        chart: {
          height: 350,
          type: "bar"
        },
        plotOptions: {
          bar: {
            horizontal: false,
            columnWidth: "55%",
            endingShape: "rounded"
          }
        },
        dataLabels: {
          enabled: false
        },
        stroke: {
          show: true,
          width: 2,
          colors: ["transparent"]
        },
        colors: ["#4788ff", "#37e6b0", "#ff4b4b"],
        series: [{
          name: "Revenue",
          data: revenueData
        }, {
          name: "Total Expenses",
          data: totalExpensesData
        }, {
          name: "Net Profit",
          data: profitData
        }],
        xaxis: {
          categories: dates
        },
        yaxis: {
          title: {
            text: "$ (thousands)"
          }
        },
        fill: {
          opacity: 1
        },
        tooltip: {
          y: {
            formatter: function(value) {
              return "$ " + value + " thousands";
            }
          }
        }
      };

      const chart = new ApexCharts(document.querySelector("#apex-column"), options);
      chart.render();

      const body = document.querySelector('body');
      if (body.classList.contains('dark')) {
        apexChartUpdate(chart, {
          dark: true
        });
      }

      document.addEventListener('ChangeColorMode', function(e) {
        apexChartUpdate(chart, e.detail);
      });
    },
    error: function(error) {
      console.error("Error fetching revenue, total expenses, and profit data: ", error);
    }
  });
}



/*---------------------------------------------------------------------
   Am Charts
-----------------------------------------------------------------------*/

if (jQuery('#am-columnlinr-chart').length) {
  // Fetch data from the PHP script
  jQuery.ajax({
      url: 'chart-dash.php', // Replace with the correct path to your PHP script
      type: 'GET',
      dataType: 'json',
      data: {
          range: 'yearly' // Adjust this based on your requirement (weekly, monthly, yearly)
      },
      success: function(response) {
          am4core.ready(function() {

              // Themes begin
              am4core.useTheme(am4themes_animated);
              // Themes end

              // Create chart instance
              var chart = am4core.create("am-columnlinr-chart", am4charts.XYChart);
              chart.colors.list = [am4core.color("#4788ff")];

              // Export
              chart.exporting.menu = new am4core.ExportMenu();

              // Transform the response data to the format required by the chart
              chart.data = response.apexColumnLineChart.map(function(item) {
                var date = new Date(item.date); // Assuming `item.date` is in YYYY-MM-DD format
                var options = { month: 'short' };
                var formattedDate = date.toLocaleDateString('en-GB', options); // Example: "Jan", "Feb"
                
                  return {
                      "date": formattedDate,    // Use the formatted month-only date
                      "revenue": item.revenue,  // Assuming `revenue` is in your response
                      "profit": item.profit     // Assuming `profit` is in your response
                  };
              });

              /* Create axes */
              var categoryAxis = chart.xAxes.push(new am4charts.CategoryAxis());
              categoryAxis.dataFields.category = "date";
              categoryAxis.renderer.minGridDistance = 30;

              /* Create value axis */
              var valueAxis = chart.yAxes.push(new am4charts.ValueAxis());

              /* Create series for revenue */
              var columnSeries = chart.series.push(new am4charts.ColumnSeries());
              columnSeries.name = "Revenue";
              columnSeries.dataFields.valueY = "revenue";
              columnSeries.dataFields.categoryX = "date";

              columnSeries.columns.template.tooltipText = "[#fff font-size: 15px]{name} in {categoryX}:\n[/][#fff font-size: 20px]{valueY}[/]";
              columnSeries.tooltip.label.textAlign = "middle";

              /* Create series for profit */
              var lineSeries = chart.series.push(new am4charts.LineSeries());
              lineSeries.name = "Profit";
              lineSeries.dataFields.valueY = "profit";
              lineSeries.dataFields.categoryX = "date";

              lineSeries.stroke = am4core.color("#4788ff");
              lineSeries.strokeWidth = 3;
              lineSeries.tooltip.label.textAlign = "middle";

              var bullet = lineSeries.bullets.push(new am4charts.Bullet());
              bullet.fill = am4core.color("#fdd400");
              bullet.tooltipText = "[#fff font-size: 15px]{name} in {categoryX}:\n[/][#fff font-size: 20px]{valueY}[/]";
              var circle = bullet.createChild(am4core.Circle);
              circle.radius = 4;
              circle.fill = am4core.color("#fff");
              circle.strokeWidth = 3;

              // Check for dark mode and update chart
              const body = document.querySelector('body');
              if (body.classList.contains('dark')) {
                  amChartUpdate(chart, { dark: true });
              }

              document.addEventListener('ChangeColorMode', function(e) {
                  amChartUpdate(chart, e.detail);
              });

          }); // end am4core.ready()
      },
      error: function(xhr, status, error) {
          console.error("Error fetching chart data: " + error);
      }
  });
}

   
if(jQuery('#am-3dpie-chart').length){
    am4core.ready(function() {

        // Themes begin
        am4core.useTheme(am4themes_animated);
        // Themes end

        $.ajax({
            url: 'chart-data.php', // Replace with the actual path to your PHP script
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                var chart = am4core.create("am-3dpie-chart", am4charts.PieChart3D);
                chart.hiddenState.properties.opacity = 0; // this creates initial fade-in

                chart.legend = new am4charts.Legend();

                chart.data = response.top5RevenueProducts.map(function(item) {
                    return {
                        product: item.product_name,
                        revenue: item.revenue
                    };
                });

                var series = chart.series.push(new am4charts.PieSeries3D());
                series.colors.list = [
                    am4core.color("#4788ff"),
                    am4core.color("#37e6b0"),
                    am4core.color("#ff4b4b"),
                    am4core.color("#fe721c"),
                    am4core.color("#876cfe")
                ];
                series.dataFields.value = "revenue";
                series.dataFields.category = "product";

                const body = document.querySelector('body');
                if (body.classList.contains('dark')) {
                    amChartUpdate(chart, {
                        dark: true
                    });
                }

                document.addEventListener('ChangeColorMode', function(e) {
                    amChartUpdate(chart, e.detail);
                });
            },
            error: function(error) {
                console.error("Error fetching top 5 revenue by product data: ", error);
            }
        });
    }); // end am4core.ready()
}

   if (jQuery('#am-layeredcolumn-chart').length) {
    // Fetch data from the PHP script
    jQuery.ajax({
        url: 'chart-dash.php', // Replace with the correct path to your PHP script
        type: 'GET',
        dataType: 'json',
        data: {
            range: 'yearly' // Adjust this based on your requirement (weekly, monthly, yearly)
        },
        success: function(response) {
            am4core.ready(function() {

                // Themes begin
                am4core.useTheme(am4themes_animated);
                // Themes end

                // Create chart instance
                var chart = am4core.create("am-layeredcolumn-chart", am4charts.XYChart);
                chart.colors.list = [am4core.color("#37e6b0"), am4core.color("#4788ff")];

                // Add percent sign and format with comma for thousands
                chart.numberFormatter.numberFormat = "#,###.##'$'";


                // Transform the response data to the format required by the chart
                chart.data = response.apexLayeredColumnChart.map(function(item) {
                    return {
                        "category": item.category_name, // Assuming `category_name` is in your response
                        "revenue": item.revenue         // Assuming `revenue` is in your response
                    };
                });

                // Create axes
                var categoryAxis = chart.xAxes.push(new am4charts.CategoryAxis());
                categoryAxis.dataFields.category = "category";
                categoryAxis.renderer.grid.template.location = 0;
                categoryAxis.renderer.minGridDistance = 30;

                var valueAxis = chart.yAxes.push(new am4charts.ValueAxis());
                valueAxis.title.text = "Revenue by Category";
                valueAxis.title.fontWeight = 800;

                // Create series for revenue
                var series = chart.series.push(new am4charts.ColumnSeries());
                series.dataFields.valueY = "revenue";
                series.dataFields.categoryX = "category";
                series.clustered = false;
                series.tooltipText = "Revenue in {categoryX}: [bold]{valueY}[/]";
                series.columns.template.width = am4core.percent(60); // Adjust width as needed

                // Cursor
                chart.cursor = new am4charts.XYCursor();
                chart.cursor.lineX.disabled = true;
                chart.cursor.lineY.disabled = true;

                // Check for dark mode and update chart
                const body = document.querySelector('body');
                if (body.classList.contains('dark')) {
                    amChartUpdate(chart, { dark: true });
                }

                document.addEventListener('ChangeColorMode', function(e) {
                    amChartUpdate(chart, e.detail);
                });

            }); // end am4core.ready()
        },
        error: function(xhr, status, error) {
            console.error("Error fetching chart data: " + error);
        }
    });
}






  /*---------------------------------------------------------------------
   Dashboard Charts
  ---------------------------------------------------------------------*/
 
// layout1-chart-3 (Profit)
if (jQuery("#layout1-chart-3").length) {
  jQuery.ajax({
    url: 'chart-dash.php',
    type: 'GET',
    dataType: 'json',
    data: { range: 'yearly' },
    success: function(response) {
      const data = response['layout1-chart-3'].map(function(item) {
        return { x: item.date, y: item.profit };
      });

      const options = {
        series: [{
          name: "Profit",
          data: data.map(item => item.y)
        }],
        colors: ['#FF7E41'],
        chart: {
          height: 150,
          type: 'line',
          zoom: {
            enabled: false
          },
          dropShadow: {
            enabled: true,
            color: '#000',
            top: 12,
            left: 1,
            blur: 2,
            opacity: 0.2
          },
          toolbar: {
            show: false
          },
          sparkline: {
            enabled: true
          }
        },
        dataLabels: {
          enabled: false
        },
        stroke: {
          curve: 'smooth',
          width: 3
        },
        title: {
          text: '',
          align: 'left'
        },
        grid: {
          row: {
            colors: ['#f3f3f3', 'transparent'],
            opacity: 0.5
          }
        },
        xaxis: {
          categories: data.map(item => item.x)
        }
      };

      const chart = new ApexCharts(document.querySelector("#layout1-chart-3"), options);
      chart.render();

      const body = document.querySelector('body');
      if (body.classList.contains('dark')) {
        apexChartUpdate(chart, { dark: true });
      }

      document.addEventListener('ChangeColorMode', function(e) {
        apexChartUpdate(chart, e.detail);
      });
    },
    error: function(xhr, status, error) {
      console.error("Error fetching chart data: " + error);
    }
  });
}


// layout1-chart-4 (Expenses)
if (jQuery("#layout1-chart-4").length) {
  jQuery.ajax({
    url: 'chart-dash.php',
    type: 'GET',
    dataType: 'json',
    data: { range: 'yearly' },
    success: function(response) {
      const data = response['layout1-chart-4'].map(function(item) {
        return { x: item.date, y: item.expenses };
      });

      const options = {
        series: [{
          name: "Expenses",
          data: data.map(item => item.y)
        }],
        colors: ['#32BDEA'],
        chart: {
          height: 150,
          type: 'line',
          zoom: {
            enabled: true
          },
          dropShadow: {
            enabled: true,
            color: '#000',
            top: 12,
            left: 1,
            blur: 2,
            opacity: 0.2
          },
          toolbar: {
            show: false
          },
          sparkline: {
            enabled: true
          }
        },
        dataLabels: {
          enabled: false
        },
        stroke: {
          curve: 'smooth',
          width: 3
        },
        title: {
          text: '',
          align: 'left'
        },
        grid: {
          row: {
            colors: ['#f3f3f3', 'transparent'], // alternates row colors
            opacity: 0.5
          }
        },
        xaxis: {
          categories: data.map(item => item.x)
        }
      };

      const chart = new ApexCharts(document.querySelector("#layout1-chart-4"), options);
      chart.render();

      const body = document.querySelector('body');
      if (body.classList.contains('dark')) {
        apexChartUpdate(chart, { dark: true });
      }

      document.addEventListener('ChangeColorMode', function(e) {
        apexChartUpdate(chart, e.detail);
      });
    },
    error: function(xhr, status, error) {
      console.error("Error fetching chart data: " + error);
    }
  });
}


// layout1-chart-5 (Profit and Expenses Combined)
if (jQuery("#layout1-chart-5").length) {
  jQuery.ajax({
    url: 'chart-dash.php',
    type: 'GET',
    dataType: 'json',
    data: { range: 'yearly' },
    success: function(response) {
      const data = {
        profit: response['layout1-chart-5'].map(item => item.profit),
        expenses: response['layout1-chart-5'].map(item => item.expenses),
        categories: response['layout1-chart-5'].map(item => item.date)
      };

      const options = {
        series: [
          { name: 'Profit', data: data.profit },
          { name: 'Expenditure', data: data.expenses }
        ],
        chart: {
          type: 'bar',
          height: 300
        },
        colors: ['#FF7E41', '#32BDEA'],
        plotOptions: {
          bar: {
            horizontal: false,
            columnWidth: '30%',
            endingShape: 'rounded'
          }
        },
        dataLabels: {
          enabled: false
        },
        stroke: {
          show: true,
          width: 3,
          colors: ['transparent']
        },
        xaxis: {
          categories: data.categories,
          labels: {
            minWidth: 0,
            maxWidth: 0
          }
        },
        yaxis: {
          show: true,
          labels: {
            minWidth: 20,
            maxWidth: 20
          }
        },
        fill: {
          opacity: 1
        },
        tooltip: {
          y: {
            formatter: function(val) {
              return "$ " + val + " ";
            }
          }
        }
      };

      const chart = new ApexCharts(document.querySelector("#layout1-chart-5"), options);
      chart.render();

      const body = document.querySelector('body');
      if (body.classList.contains('dark')) {
        apexChartUpdate(chart, { dark: true });
      }

      document.addEventListener('ChangeColorMode', function(e) {
        apexChartUpdate(chart, e.detail);
      });
    },
    error: function(xhr, status, error) {
      console.error("Error fetching chart data: " + error);
    }
  });
}


  
})(jQuery);


