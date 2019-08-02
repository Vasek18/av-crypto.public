<template>
    <form class="add-basket-form"
          :action="action"
          method="post"
          ref="form"
          @submit.prevent="onSubmit"
    >
        <h2>Создать корзинку</h2>
        <ul class="text-danger"
            v-if="errors">
            <li v-for="error in errors">{{ error }}</li>
        </ul>
        <div class="row">
            <div class="form-group col-md-4"
                 v-if="balances.length">
                <label for="currency_1">Валюта 1</label>
                <select id="currency_1"
                        name="currency_1"
                        class="form-control"
                        @change="onSelectFirstCurrency"
                        required
                >
                    <option value=""
                            :selected="currency_1 == '' ? true : false"
                    >Выберите
                    </option>
                    <option v-for="balance in balances"
                            v-if="balance.amount>0"
                            :value="balance.currency.code"
                            :selected="currency_1 == balance.currency.code ? true : false"
                    >{{ balance.currency.code }}
                    </option>
                </select>
            </div>
            <div class="form-group col-md-4"
                 v-if="currencies_2.length">
                <label for="currency_2">Валюта 2</label>
                <select id="currency_2"
                        name="currency_2"
                        class="form-control"
                        @change="onSelectSecondCurrency"
                        required
                >
                    <option value=""
                            :selected="currency_2 =='' ? true : false"
                    >Выберите
                    </option>
                    <option v-for="currency in currencies_2"
                            :value="currency"
                            :selected="currency_2 == currency ? true : false"
                    >{{ currency }}
                    </option>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="start_sum">Сумма</label>
                <input type="text"
                       id="start_sum"
                       name="start_sum"
                       class="form-control"
                       :value="start_sum"
                       @change="onChangeStartSum"
                       required
                >
            </div>
        </div>
        <div class="form-group"
             v-if="currency_1 && currency_2 && start_sum"
        >
            <button id="create"
                    name="create"
                    class="btn btn-success btn-block">Создать
            </button>
        </div>
    </form>
</template>
<script>
    export default {
        props: {
            balances: {},
            exchange: {},
            account: {},
        },
        data: function () {
            return {
                currencies: [],
                currencies_2: [],
                currency_pairs: [],
                currency_pair: {},
                maxStartSum: 0,
                minStartSum: 0,
                start_sum: 0,
                currency_1: '',
                currency_2: '',
                action: 'basket',
                errors: []
            }
        },

        mounted: function () {
            this.getPairs();
        },

        watch: {
            exchange: function () {
                this.getPairs();
            }
        },

        methods: {
            getPairs: function () {
                let vm = this;

                $.ajax({
                    url: 'exchange/' + vm.exchange.id + '/currency_pairs',
                    data: {},
                    type: "get",
                    dataType: "json",
                    success: function (answer) {
                        vm.currency_pairs = answer.currency_pairs;
                    },
                    error: function (e) {
                    }
                });
            },
            onSelectFirstCurrency: function (event) {
                let vm = this;

                let selectedCurrencyCode = event.target.value;
                vm.currency_1 = selectedCurrencyCode;

                // узнаём границы допустимого начального значения
                vm.balances.forEach(function (balance) {
                    if (balance.currency.code == selectedCurrencyCode) {
                        vm.maxStartSum = balance.amount;
                        vm.start_sum = vm.maxStartSum; // сразу же ставим в инпут максимальное значение
                    }
                });

                // получаем валюты, которые торгуются с этой валютой для второго селекта
                vm.currencies_2 = [];
                vm.currency_pairs.forEach(function (currency_pair) {
                    if (currency_pair.currency_1_code == vm.currency_1) {
                        vm.currencies_2.push(currency_pair.currency_2_code);
                    }
                });
            },

            onSelectSecondCurrency: function (event) {
                let vm = this;

                vm.currency_2 = event.target.value;

                // получаем информацию про выбранную валютную пару
                vm.currency_pair = {};
                vm.currency_pairs.forEach(function (currency_pair) {
                    if (currency_pair.currency_1_code == vm.currency_1) {
                        if (currency_pair.currency_2_code == vm.currency_2) {
                            // переопределяем максимумы и минимумы
                            if (currency_pair.currency_1_max_amount < vm.maxStartSum) {
                                vm.maxStartSum = currency_pair.currency_1_max_amount;
                            }
                            if (currency_pair.currency_1_min_amount > vm.minStartSum) {
                                vm.minStartSum = currency_pair.currency_1_min_amount;
                            }
                        }
                    }
                });
            },

            onChangeStartSum: function (event) {
                let vm = this;

                if (!event.target.value.length) {
                    return;
                }

                // связываем значение с переменной
                let sum = Number(event.target.value);

                if (isNaN(sum)) {
                    sum = vm.minStartSum;
                }

                vm.start_sum = sum;
                // валидируем значение
                if (sum > vm.maxStartSum) {
                    vm.start_sum = vm.maxStartSum;
                }
                if (sum < vm.minStartSum) {
                    vm.start_sum = vm.minStartSum;
                }
            },

            onSubmit: function () {
                let vm = this;

                vm.errors = [];

                // валидируем значение
                if (vm.start_sum > vm.maxStartSum) {
                    vm.errors.push('Значение больше допустимого');
                    return;
                }
                if (vm.start_sum < vm.minStartSum) {
                    vm.errors.push('Значение меньше допустимого');
                    return;
                }

                $.ajax({
                    url: this.action,
                    data: {
                        start_sum: vm.start_sum,
                        currency_1: vm.currency_1,
                        currency_2: vm.currency_2,
                        exchange_market_id: vm.exchange.id,
                        account_id: vm.account.id,
                        _token: csrf_token,
                    },
                    method: "post",
                    dataType: "json",
                    success: function (answer) {
                        if (answer.basket) {
                            vm.$emit('added', answer.basket);
                        }
                        else {
                            if (answer.errors) {
                                vm.errors = answer.errors;
                            }
                            else {
                                vm.errors.push('Ошибка');
                            }
                        }
                    },
                    error: function (e) {
                        vm.errors.push('Ошибка! Повторите попытку позже');
                    }
                });
            }
        },
    }
</script>
<style lang="scss">
</style>