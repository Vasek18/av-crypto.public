<template>
    <div class="card basket account-baskets__item">
        <div class="card-body basket__body">
            <h3 class="card-title text-success basket__title">{{ currentValue }} {{ currentCurrency }} </h3>
            <p class="card-text basket__text"> {{ basket.currency_pair.currency_1_code }} <-> {{
                basket.currency_pair.currency_2_code }} <span
                    class="text-success">{{progressPercent}}</span>
            </p>
            <p class="card-text basket__text" v-if="lastOrder.created_at">
                Изменена: {{ lastOrder.created_at | formatDate }}
            </p>
            <button type="button"
                    class="btn btn-primary"
                    data-toggle="modal"
                    :data-target="'#basketModal'+basket.id"
                    dusk="basket-show-detail-btn"
            >
                Подробнее
            </button>
            <div class="modal fade"
                 tabindex="-1"
                 :id="'basketModal'+basket.id"
                 role="dialog"
                 aria-hidden="true">
                <div class="modal-dialog modal-lg"
                     role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <span class="text-success">{{ currentValue }} {{ currentCurrency }}</span>
                                <small>({{ basket.currency_pair.currency_1_code}} <-> {{
                                    basket.currency_pair.currency_2_code }})
                                </small>
                                <span class="text-success">| {{ progressPercent }} |</span>
                                <small class="basket__date">Создана: {{ basket.created_at | formatDate }}</small>
                            </h5>
                            <button type="button"
                                    class="close"
                                    data-dismiss="modal"
                                    aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-bordered"
                                       v-if="basket.orders && basket.orders.length">
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
                                    <tr v-for="order in basket.orders"
                                        :class="{'table-secondary': order.done, 'table-success': !order.done}">
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
                        <div class="modal-footer">
                            <button type="button"
                                    class="btn btn-danger"
                                    @click.prevent='onDeleteBasketBtnClick'
                                    dusk="basket-delete-btn"
                            >Удалить корзинку
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        props: {
            basket: {}
        },
        data: function () {
            return {}
        },

        mounted: function () {
        },

        computed: {
            lastOrder: function () {
                let orders = this.basket.orders;
                if (orders && orders.length >= 1) {
                    return orders[0]; // ордера идут в обратном порядке
                }

                return false;
            },
            currentValue: function () {
                let lastAmount = this.basket.next_action == 'buy' ? this.basket.currency_2_last_amount : this.basket.currency_1_last_amount;
                return lastAmount ? lastAmount : this.basket.start_sum;
            },
            currentCurrency: function () {
                return this.basket.next_action == 'buy' ? this.basket.currency_pair.currency_2_code : this.basket.currency_pair.currency_1_code
            },
            progressPercent: function () {
                let orders = this.basket.orders;
                if (orders && orders.length > 1) {
                    let lastDoneOrderAmount = 0;
                    let orderMaxNumber = orders.length - 1;
                    for (let i = 0; i < orderMaxNumber; i++) { // ище первый "с конца" выполненный ордер
                        let order = orders[i];
                        if (order.done) {
                            lastDoneOrderAmount = order.amount;
                            break;
                        }
                    }
                    let firstDoneOrderAmount = orders[orderMaxNumber].amount;

                    let percent = (lastDoneOrderAmount - firstDoneOrderAmount) / (firstDoneOrderAmount / 100);
                    percent = Math.round(percent * 100) / 100;
                    if (percent > 0) {
                        return '+' + percent + '%';
                    }
                    if (percent < 0) {
                        return '-' + percent + '%';
                    }
                }
            }
        },

        methods: {
            onDeleteBasketBtnClick: function () {
                let vm = this;

                $.ajax({
                    url: 'basket/' + vm.basket.id,
                    data: {
                        _method: 'DELETE',
                        _token: csrf_token,
                    },
                    type: "POST",
                    dataType: "json",
                    success: function (answer) {
                        if (answer.success) {
                            $('#basketModal' + vm.basket.id).modal('toggle'); // странно, но при удалении компонента не закрывается окно, а автоматически открывется окно следующей корзинки
                            vm.$emit('delete', vm.basket.id);
                        } else {
                            showMessage(answer.message ? answer.message : 'Произошла ошибка! Попробуйте ещё раз');
                        }
                    },
                    error: function (e) {
                        showMessage('Произошла ошибка! Попробуйте ещё раз');
                    }
                });
            },
        }
    }
</script>
<style lang="scss">
    .account-baskets__item {
        margin-top: 10px;
        margin-bottom: 10px;
    }
</style>