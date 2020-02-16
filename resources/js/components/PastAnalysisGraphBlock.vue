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
        <div class="row" v-if="events.length">
            <div class="col-md-12">
                <label>События</label>
                <select class="form-control" @change="onEventsChoose" multiple>
                    <option value="">Выберите</option>
                    <option v-for="eventVariant in eventVariants" :value="eventVariant">{{ eventVariant }}</option>
                </select>
            </div>
        </div>
        <div class="row" v-if="traders.length">
            <div class="col-md-12">
                <label>Решения трейдеров</label>
                <select class="form-control" @change="onTraderChoose" multiple>
                    <option value="">Выберите</option>
                    <option v-for="trader in traders" :value="trader">{{ trader }}</option>
                </select>
            </div>
        </div>
        <hr>
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
                   :class="['btn btn-success btn-block', {'disabled':!loaded}]"
                   @click.prevent="toggleGraph"
                >{{ showGraph ? 'Скрыть график' : 'Построить график' }}
                </a>
            </div>
            <rates-graph v-if="showGraph && rates.length"
                         :rates="rates"
                         :orders="orders"
                         :metrics="filteredMetrics"
                         :decisions="filteredDecisions"
                         :events="filteredEvents"
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
            events: {},
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
                chosen_events: [],
                chosen_traders: [],
                filteredMetrics: [],
                filteredEvents: [],
                filteredDecisions: [],
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
            },
            eventVariants: function () {
                let vm = this;
                let variants = [];

                this.events.forEach(function (event) {
                    let eventID = vm.getEventID(event);
                    if (variants.indexOf(eventID) === -1) {
                        variants.push(eventID);
                    }
                });

                variants.sort();

                return variants;
            },
            traders: function () {
                let traders = [];

                this.decisions.forEach(function (decision) {
                    let trader = decision.trader_code;
                    if (traders.indexOf(trader) === -1) {
                        traders.push(trader);
                    }
                });

                traders.sort();

                return traders;
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
                this.updateChosenMetrics(e.target.options);
                this.filterMetrics();
            },
            updateChosenMetrics: function (selectOptions) {
                // такое сложное получение, потому что из нескольких селектов собираем инфу
                for (let i = 0, l = selectOptions.length; i < l; i++) {
                    let indexInChosenMetrics = this.chosen_metrics.indexOf(selectOptions[i].value);
                    if (selectOptions[i].selected) {
                        if (indexInChosenMetrics === -1) { // если ещё нет в массиве выбранных метрик
                            this.chosen_metrics.push(selectOptions[i].value);
                        }
                    } else {
                        if (indexInChosenMetrics !== -1) {
                            this.chosen_metrics.splice(indexInChosenMetrics, 1);
                        }
                    }
                }
            },
            filterMetrics: function () {
                let vm = this;

                let filteredMetrics = [];
                this.metrics.forEach(function (metric) {
                    if (vm.chosen_metrics.indexOf(metric.code) !== -1) {
                        filteredMetrics.push(metric);
                    }
                });

                vm.filteredMetrics = filteredMetrics;
            },
            onEventsChoose: function (e) {
                this.updateChosenEvents(e.target.options);
                this.filterEvents();
            },
            updateChosenEvents: function (options) { // todo у событий всего 1 селект, в теории можно заменить сбор на простое присвоение выбранных опшионов + можно будет вообще избавиться от chosen_events
                for (let i = 0, l = options.length; i < l; i++) {
                    let indexInChosenEvents = this.chosen_events.indexOf(options[i].value);
                    if (options[i].selected) {
                        if (indexInChosenEvents === -1) { // если ещё нет в массиве выбранных событиях
                            this.chosen_events.push(options[i].value);
                        }
                    } else {
                        if (indexInChosenEvents !== -1) {
                            this.chosen_events.splice(indexInChosenEvents, 1);
                        }
                    }
                }
            },
            filterEvents: function () {
                let vm = this;

                let filteredEvents = [];
                vm.events.forEach(function (event) {
                    if (vm.chosen_events.indexOf(vm.getEventID(event)) !== -1) {
                        filteredEvents.push(event);
                    }
                });

                vm.filteredEvents = filteredEvents;
            },
            getEventID: function (event) {
                let variant = event.type;
                if (Object.entries(event.params).length !== 0) {
                    variant = variant + ' ' + JSON.stringify(event.params);
                }

                return variant;
            },
            onTraderChoose: function (e) {
                this.updateChosenTraders(e.target.options);
                this.filterTradersDecisions();
            },
            updateChosenTraders: function (options) { // todo мб можно упростить до простого присваивания всех выбранных вариантов и избавления от chosen_traders
                for (let i = 0, l = options.length; i < l; i++) {
                    let indexInChosenTraders = this.chosen_traders.indexOf(options[i].value);
                    if (options[i].selected) {
                        if (indexInChosenTraders === -1) { // если ещё нет в массиве выбранных событиях
                            this.chosen_traders.push(options[i].value);
                        }
                    } else {
                        if (indexInChosenTraders !== -1) {
                            this.chosen_traders.splice(indexInChosenTraders, 1);
                        }
                    }
                }
            },
            filterTradersDecisions: function () {
                let vm = this;

                let filteredDecisions = [];
                this.decisions.forEach(function (decision) {
                    if (vm.chosen_traders.indexOf(decision.trader_code) !== -1) {
                        filteredDecisions.push(decision);
                    }
                });

                vm.filteredDecisions = filteredDecisions;
            },
        },
    }
</script>
<style lang="scss">
</style>