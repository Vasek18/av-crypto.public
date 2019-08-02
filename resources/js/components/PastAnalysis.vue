<template>
    <div class="past-analysis">
        <past-analysis-form
                :currency_pairs="currency_pairs"
                :exchange_markets="exchange_markets"
                :loaded="loaded"
                @formChange="onFormChange"
        ></past-analysis-form>
        <div class="row"
             v-if="Object.keys(currency_pair).length">
            <div class="form-group col-md-12">
                <a href="#"
                   id="get_pair_info"
                   :class="['btn btn-success btn-block', {'disabled':!loaded}]"
                   @click.prevent="getPairInfo"
                >Получить информацию
                </a>
            </div>
        </div>
        <past-analysis-graph-block v-if="rates.length"
                                   :rates="rates"
                                   :orders="orders"
                                   :metrics="metrics"
                                   :decisions="decisions"
                                   :trends="trends"
                                   :dateFrom="dateFrom"
                                   :dateTo="dateTo"
                                   :currencyPair="currency_pair"
        ></past-analysis-graph-block>
        <br>
        <div class="past-analysis__tables row">
            <div class="col-sm-6 col-xs-12" v-if="orders.length">
                <h2>Ордера</h2>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Количество</th>
                            <th>Полученное количество</th>
                            <th>Цена</th>
                            <th>Действие</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="order in orders"
                            :class="{'table-info': order.action == 'buy', 'table-success': order.action == 'sell'}">
                            <td>{{ order.created_at | formatDate }}</td>
                            <td>{{ order.amount }}</td>
                            <td>{{ order.gained_amount }}</td>
                            <td>{{ order.price }}</td>
                            <td>{{ order.action == 'buy' ? 'Покупка' : order.action == 'sell' ? 'Продажа' :
                                '' }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-sm-6 col-xs-12" v-if="decisions.length">
                <h2>Решения</h2>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Трейдер</th>
                            <th>Действие</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="decision in decisions"
                            :class="{'table-info': decision.decision == 'B', 'table-success': decision.decision == 'S'}">
                            <td>{{ decision.timestamp | formatDate }}</td>
                            <td>{{ decision.trader_code }}</td>
                            <td>{{ decision.decision == 'B' ? 'Покупка' : decision.decision == 'S' ? 'Продажа' :
                                '' }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        props: {
            currency_pairs: {},
            exchange_markets: {},
            get_pair_info_action: '',
        },

        data: function () {
            return {
                currency_pair: {},
                getPairInfoRequestType: 'get',
                dateFrom: '',
                dateTo: '',
                rates: [],
                orders: [],
                metrics: [],
                decisions: [],
                trends: [],
                loaded: true,
                errors: [],
            }
        },

        mounted: function () {
            let vm = this;
        },

        computed: {},

        methods: {
            onFormChange: function (formFields) {
                let vm = this;

                vm.currency_pair = formFields.currencyPair;
                vm.dateFrom = formFields.dateFrom;
                vm.dateTo = formFields.dateTo;

                vm.clearTestResults();
            },
            clearTestResults: function () {
                let vm = this;

                vm.orders.splice(0, vm.orders.length);
                vm.isSuccess = false;
            },
            getPairInfo: function () {
                let vm = this;

                vm.loaded = false;

                $.ajax({
                    url: vm.get_pair_info_action,
                    data: {
                        currency_pair_id: vm.currency_pair.id,
                        currency_1_code: vm.currency_pair.currency_1_code,
                        currency_2_code: vm.currency_pair.currency_2_code,
                        exchange_market_id: vm.currency_pair.exchange_market_id,
                        dateFrom: vm.dateFrom,
                        dateTo: vm.dateTo,
                    },
                    method: vm.getPairInfoRequestType,
                    dataType: "json",
                    success: function (answer) {
                        if (answer.orders) {
                            vm.orders = answer.orders;
                        }
                        if (answer.rates) {
                            vm.rates = answer.rates;
                        }
                        if (answer.metrics) {
                            vm.metrics = answer.metrics;
                        }
                        if (answer.decisions) {
                            vm.decisions = answer.decisions;
                        }
                        if (answer.trends) {
                            vm.trends = answer.trends;
                        }

                        vm.loaded = true;
                    },
                    error: function (e) {
                        vm.errors.push('Ошибка! Повторите попытку позже');
                    }
                });
            },
        },
    }
</script>
<style lang="scss">
</style>