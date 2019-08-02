<template>
    <form class="past-analysis-graph-block">
        <hr>
        <div class="row">
            <div class="col-md-2 mb-4" v-for="group in groupedMetrics">
                <label>{{ group.name }}</label>
                <select class="form-control" :name="group.name" @change="onMetricsChoose" multiple>
                    <option value="">Выберите</option>
                    <option v-for="metric in group.metrics" :value="metric.code">{{ metric.name }}</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-2">
                <label>Шаг графика</label>
            </div>
            <div class="form-group col-md-4">
                <input type="text"
                       id="graphStep"
                       name="graphStep"
                       class="form-control"
                       :value="graphStep"
                       @input="graphStep = $event.target.value"
                       placeholder="Шаг графика"
                       required
                >
            </div>
            <div class="col-md-6">
                <a href="#"
                   id="show_graph"
                   name="show_graph"
                   :class="['btn btn-success btn-block', {'disabled':!loaded}]"
                   @click.prevent="toggleGraph"
                >{{ showGraph ? 'Скрыть график' : 'Построить график' }}
                </a>
            </div>
            <rates-graph v-if="showGraph && rates.length"
                         :rates="rates"
                         :orders="orders"
                         :metrics="metrics"
                         :chosen_metrics="chosen_metrics"
                         :decisions="decisions"
                         :trends="trends"
                         :step="graphStep"
                         @loaded="graphLoaded"
            ></rates-graph>
        </div>
    </form>
</template>
<script>
    export default {
        props: {
            orders: {},
            rates: {},
            metrics: {},
            decisions: {},
            trends: {},
            dateFrom: '',
            dateTo: '',
            currencyPair: {},
        },

        data: function () {
            return {
                graphStep: 10,
                showGraph: false,
                loaded: true,
                chosen_metrics: [],
                errors: [],
            }
        },

        mounted: function () {
            let vm = this;

        },

        computed: {
            groupedMetrics: function () {
                let grouped = {};

                this.metrics.forEach(function (metric) {
                    if (!grouped[metric.group_name]) {
                        grouped[metric.group_name] = {name: metric.group_name, metrics: [metric]};
                    } else {
                        grouped[metric.group_name].metrics.push(metric);
                    }
                });

                return grouped;
            }
        },

        methods: {
            toggleGraph: function () {
                this.showGraph = !this.showGraph;
            },
            graphLoaded: function (loaded) {
                this.loaded = loaded;
            },
            onMetricsChoose: function (e) {
                let options = e.target.options;

                for (let i = 0, l = options.length; i < l; i++) {
                    let indexInChosenMetrics = this.chosen_metrics.indexOf(options[i].value);
                    if (options[i].selected) {
                        if (indexInChosenMetrics === -1) { // если ещё нет в массиве выбранных метрик
                            this.chosen_metrics.push(options[i].value);
                        }
                    } else {
                        if (indexInChosenMetrics !== -1) {
                            this.chosen_metrics.splice(indexInChosenMetrics, 1);
                        }
                    }
                }

            }
        },
    }
</script>
<style lang="scss">
</style>