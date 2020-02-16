<template>
    <div class="rates-graph"
         dusk="rates-graph"
         v-if="rates.length">
        <div class="rates-graph__canvas-wrapper"
             ref="wrapper"
        >
            <div class="rates-graph__canvas-inner-wrapper"
                 :style="{ width: width+'px' }">
                <canvas ref="ratesChart"
                        class="rates-graph__canvas"
                ></canvas>
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        props: {
            rates: {},
            orders: {},
            metrics: {},
            decisions: {},
            events: {},
            step: {
                default: 1
            },
        },

        data: function () {
            return {
                stepInPixels: 15,
                xAxes: [{}],
                yAxes: [{}],
                datasets: [],
                annotations: [],
                firstTimestampAtGraph: false,
                lastTimestampAtGraph: false,
                labels: [],
                graph: false,
                colorsUseCount: 0
            }
        },

        mounted: function () {
            if (this.rates.length) {
                this.makeGraph();
            }
        },

        watch: {
            rates: function () {
                this.makeGraph();
            },
            orders: function () {
                this.makeGraph();
            },
            metrics: function () {
                this.makeGraph();
            },
            decisions: function () {
                this.makeGraph();
            },
            events: function () {
                this.makeGraph();
            },
            step: function () {
                this.makeGraph();
            }
        },

        computed: {
            stepForGraph: function () {
                let step = Number(this.step);

                if (!step) {
                    step = 1;
                }

                return step;
            },
            width: function () {
                return Math.max(this.rates.length / this.stepForGraph * this.stepInPixels, 1000);
            },
            minimumPrice: function () {
                let min = false;
                for (let i = 0; i < this.rates.length; i = i + this.stepForGraph) {
                    if (this.rates[i]) {
                        let rate = this.rates[i];
                        if (min === false || rate.buy_price < min) {
                            min = rate.buy_price; // buy_price всегда меньше sell_price
                        }
                    }
                }

                return min;
            },
        },

        methods: {
            getRandomColor: function () {
                let firstDopColors = [ // цвета должны быть максимально контрастные, поэтому по крайней мере первые хардкодим
                    '#7CFC00', // ярко зелёный
                    '#00FFFF', // бирюзовый
                    '#FF8C00', // оранжевый
                    '#FF1493', // лиловый
                    '#228B22', // зелёный
                    '#FFD700', // жёлтый
                    '#F0E68C', // хаки
                    '#000000', // чёрный
                    '#FFC0CB', // розовый
                    '#A0522D', // коричневый
                ];

                if (color = firstDopColors[this.colorsUseCount]) {
                    this.colorsUseCount++;
                    return color;
                }

                // если нет заготовленного цвета, то получаем случайный
                var letters = '0123456789ABCDEF'.split('');
                var color = '#';
                for (var i = 0; i < 6; i++) {
                    color += letters[Math.floor(Math.random() * 16)];
                }

                this.colorsUseCount++;

                return color;
            },
            gainMetricsEntities: function () {
                let vm = this;
                let data = {};

                if (!vm.metrics) {
                    return false;
                }

                let yBorders = {};
                let annotationValues = [];

                // собираем датасеты
                this.metrics.forEach(function (metric) {
                    metric.values.forEach(function (value) {
                        let color = vm.getRandomColor();
                        // начинаем массивы метрик
                        if (!data[metric.code]) {
                            switch (metric.type) {
                                case 'extremum': // просто точки на диаграмме
                                    data[metric.code] = {
                                        label: metric.code,
                                        data: [],
                                        borderColor: color,
                                        showLine: false,
                                        xAxisID: 'default-x-axis',
                                        backgroundColor: color,
                                        pointRadius: 5
                                    };
                                    break;
                                case 'macd':
                                case 'sell_quantity':
                                case 'buy_amount':
                                case 'spread':
                                    data[metric.code] = {
                                        label: metric.code,
                                        data: [],
                                        borderColor: color,
                                        type: 'line',
                                        yAxisID: metric.code + '-y-axis',
                                        xAxisID: 'default-x-axis',
                                        backgroundColor: 'transparent',
                                        pointRadius: 1
                                    };
                                    break;
                                default: // метрики, у которых значения это цены
                                    data[metric.code] = {
                                        label: metric.code,
                                        data: [],
                                        borderColor: color,
                                        backgroundColor: 'transparent',
                                        pointRadius: 1
                                    };
                                    break;
                            }
                        }

                        if (data[metric.code]) { // если по метрике собираем значения для графика
                            // собираем максимумы и минимумы для оси абсцисс
                            if (!yBorders[metric.code]) {
                                yBorders[metric.code] = {
                                    maxValue: 0,
                                    minValue: 0,
                                    type: metric.type,
                                };
                            }
                            if (yBorders[metric.code].maxValue < value.value) {
                                yBorders[metric.code].maxValue = Number(value.value);
                            }
                            if (yBorders[metric.code].minValue > value.value) {
                                yBorders[metric.code].minValue = Number(value.value);
                            }

                            // записываем конкретные значения
                            data[metric.code].data.push({
                                x: Number(value.timestamp),
                                y: Number(value.value),
                            });
                        }
                    });
                });

                // шаги и горизонтальная ось // неежемиутные метрики не должны делить свои значения по у на шаг, так как график не сдвигается, а просто берёт промежуточные значения при шаге > 1
                let tempArr;
                for (let metricCode in data) {
                    if (data[metricCode]) {
                        if (data[metricCode].data) {
                            // если метрика собирается каждую минуту (как котировки) или чаще, то они тоже должны учитывать шаг
                            if (data[metricCode].data.length >= vm.rates.length) {
                                tempArr = [];
                                for (let i = 0; i < data[metricCode].data.length; i = i + vm.stepForGraph) {
                                    if (data[metricCode].data[i]) {
                                        tempArr.push(data[metricCode].data[i]);
                                    }
                                }
                                data[metricCode].data = tempArr
                            }
                        }
                    }
                }

                // создаём свои оси для метрик, которые не похожи на котировки
                for (let metricCode in yBorders) {
                    let borders = yBorders[metricCode];
                    if ((['macd', 'sell_quantity', 'buy_amount', 'spread'].indexOf(borders.type) !== -1) || (metricCode.indexOf('macd_') === 0)) {
                        vm.yAxes.push({
                                id: metricCode + '-y-axis',
                                type: 'linear',
                                position: 'right',
                                display: false,
                                ticks: {
                                    min: borders.minValue,
                                    max: borders.maxValue * 3,
                                }
                            }
                        );
                    }
                }

                // объединяем датасеты метрик с остальными датасетами
                for (let dataset in data) {
                    vm.datasets.push(data[dataset]);
                }

                if (annotationValues.length) {
                    vm.annotations = vm.annotations.concat(annotationValues);
                }
            },
            getBuyDataset: function () {
                let vm = this;

                let data = [];
                this.rates.forEach(function (rate, i) {
                    if (i % vm.stepForGraph == 0) {
                        data.push({x: rate.timestamp, y: rate.buy_price});
                    }
                });

                let dataset = {
                    label: 'Покупка',
                    data: data,
                    borderColor: 'blue',
                    backgroundColor: 'transparent',
                    pointRadius: 1,
                    xAxisID: 'default-x-axis'
                };
                vm.datasets.push(dataset);

                return dataset;
            },
            getFirstAndLastTimestamps: function (buyDataset) {
                let vm = this;

                // собираем крайние точки на графике
                vm.firstTimestampAtGraph = buyDataset.data[0].x;
                vm.lastTimestampAtGraph = buyDataset.data[buyDataset.data.length - 1].x;
            },
            getLabels: function (buyDataset) {
                let vm = this;

                // значения для оси абсцисс
                let labels = [];
                for (let i = 0; i < buyDataset.data.length; i++) {
                    if (buyDataset.data[i]) {
                        let rate = buyDataset.data[i];
                        labels.push(DateTimeHelper.formatDateTime(rate.x));
                    }
                }
                vm.labels = labels;
            },
            getSellDataset: function () {
                let vm = this;

                let data = [];
                this.rates.forEach(function (rate, i) {
                    if (i % vm.stepForGraph == 0) {
                        data.push({x: rate.timestamp, y: rate.sell_price});
                    }
                });

                vm.datasets.push({
                    label: 'Продажа',
                    data: data,
                    borderColor: 'red',
                    backgroundColor: 'transparent',
                    pointRadius: 1,
                    xAxisID: 'default-x-axis'
                });
            },
            getOrdersEntities: function () {
                let vm = this;

                let annotationValues = [];

                this.orders.forEach(function (order, i) {
                    var timestamp = order.timestamp;
                    if (!timestamp) {
                        if (order.created_at) {
                            timestamp = new Date(order.created_at).getTime() / 1000
                        }
                    }

                    // значения для вертикальных линий
                    annotationValues.push(
                        {
                            type: "line",
                            mode: "vertical",
                            scaleID: "default-x-axis",
                            value: timestamp,
                            borderColor: order.action == 'buy' ? 'blue' : 'red',
                            label: {
                                content: order.price,
                                enabled: true,
                                position: "top"
                            }
                        }
                    );
                });

                // аннотация для вертикальных линий
                if (annotationValues.length) {
                    vm.annotations = vm.annotations.concat(annotationValues);
                }
            },
            getEventsEntities: function () {
                let vm = this;

                let annotationValues = [];

                this.events.forEach(function (event) {
                    // значения для вертикальных линий
                    annotationValues.push(
                        {
                            type: "line",
                            mode: "vertical",
                            scaleID: "default-x-axis",
                            value: event.timestamp,
                            borderColor: 'orange',
                            label: {
                                content: event.type,
                                enabled: true,
                                position: "top"
                            }
                        }
                    );
                });

                // аннотация для вертикальных линий
                if (annotationValues.length) {
                    vm.annotations = vm.annotations.concat(annotationValues);
                }
            },
            getDecisionsAnnotations: function () {
                let vm = this;
                let annotationValues = [];

                this.decisions.forEach(function (decision, i) {
                    // значения для вертикальных линий
                    annotationValues.push(
                        {
                            type: "line",
                            mode: "vertical",
                            scaleID: "default-x-axis",
                            value: decision.timestamp,
                            borderColor: decision.decision == 'B' ? 'blue' : 'red',
                            label: {
                                content: decision.trader_code,
                                enabled: true,
                                position: "top"
                            }
                        }
                    );
                });

                // аннотация для вертикальных линий
                if (annotationValues.length) {
                    vm.annotations = vm.annotations.concat(annotationValues);
                }

                return annotationValues;
            },
            getDefaultXAxis: function () {
                let vm = this;
                vm.xAxes.push({
                        id: 'default-x-axis',
                        type: 'linear',
                        position: 'bottom',
                        display: false,
                        ticks: {
                            min: vm.firstTimestampAtGraph,
                            max: vm.lastTimestampAtGraph,
                        }
                    }
                );
            },
            clearGraph: function () {
                let vm = this;

                this.colorsUseCount = 0; // очищаем подсчёт цветов, иначе при изменениях списка графиков быстро выйдем за лимит
                vm.datasets.splice(0, vm.datasets.length); // очищаем датасеты
                if (vm.annotations) {
                    vm.annotations.splice(0, vm.annotations.length); // очищаем аннотации
                } else {
                    vm.annotations = [];
                }
                vm.xAxes.splice(0, vm.xAxes.length); // очищаем оси
                vm.yAxes.splice(0, vm.yAxes.length); // очищаем оси
                vm.xAxes.push({}); // всегда должна быть пустая ось для отображения графика в принципе
                vm.yAxes.push({}); // всегда должна быть пустая ось для отображения графика в принципе
            },
            makeGraph: function () {
                let vm = this;

                vm.$emit('loaded', false);

                vm.clearGraph();

                let buyDataset = vm.getBuyDataset();
                if (buyDataset) {
                    vm.getFirstAndLastTimestamps(buyDataset);
                    vm.getLabels(buyDataset);
                    vm.getDefaultXAxis();
                    vm.getSellDataset();
                    vm.getOrdersEntities();
                    vm.gainMetricsEntities();
                    vm.getDecisionsAnnotations();
                    vm.getEventsEntities();

                    let ctx = this.$refs.ratesChart.getContext('2d');

                    // если мы перерисовываем график, то мы сначала удаляем его и создаём новый. Update показал себя не очень хорошо
                    if (vm.graph !== false) {
                        vm.graph.destroy();
                    }

                    vm.graph = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: vm.labels,
                            datasets: vm.datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            tooltips: {
                                mode: 'nearest',
                                intersect: false,
                            },
                            scales: {
                                xAxes: vm.xAxes,
                                yAxes: vm.yAxes,
                            },
                            annotation: {
                                annotations: vm.annotations
                            },
                            legend: {
                                labels: {
                                    filter: function (item, chart) {
                                        // скрываем из легенды названия датасетов трендов, так как они лишние там
                                        return !item.text.includes('Тренд ');
                                    }
                                }
                            }
                        }
                    });
                } else {
                    // todo вывод ошибок
                }

                vm.$emit('loaded', true);
            }
        },
    }
</script>
<style lang="scss">
    .rates-graph {
        width: 100%;
    }

    .rates-graph__canvas-wrapper {
        overflow-x: scroll;
        position: relative;
        width: 100%;
    }

    .rates-graph__canvas-inner-wrapper {
        position: relative;
        height: 700px;
    }

    .rates-graph__canvas {
        position: absolute;
        left: 0;
        top: 0;
        pointer-events: none;
    }
</style>