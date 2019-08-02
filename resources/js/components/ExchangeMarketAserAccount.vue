<template>
	<div class="exm-user-account"
	     v-if="exchange">
		<h1>Биржа "{{ exchange.name }}"</h1>
		<div class="account-balances"
		     v-if="balances.length">
			<hr>
			<h2>Балансы</h2>
			<div class="row account-balances__list">
				<div class="col-md-2"
				     v-for="balance in balances"
				     v-if="balance.amount>0"
				>
					<div class="card account-balances__item">
						<div class="card-body">
							<h3 class="card-title">{{ balance.currency.code }}</h3>
							<p class="card-text">{{ balance.amount }}</p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="account-baskets"
		     v-if="baskets.length">
			<hr>
			<h2>Корзинки
				<small class="text-muted"
				       data-toggle="tooltip"
				       data-placement="top"
				       title="Корзинка это та сумма, которая будет использоваться для автотрейдинга через данный сервис">Что это?
				</small>
			</h2>
			<div class="row account-baskets__list">
				<div class="col-md-3"
				     v-for="basket in baskets"
				>
					<basket :basket="basket"
					        @delete="onBasketDeletion"
					></basket>
				</div>
			</div>
		</div>
		<add-basket-form v-if="balances.length"
		                 :balances="balances"
		                 :exchange="exchange"
		                 :account="user_account"
		                 @added="onBasketAdded"
		></add-basket-form>
	</div>
</template>
<script>
	export default {
		props: {
			user_account: {}
		},
		data : function(){
			return {
				balancesWithoutBaskets: [],
				balances              : [],
				exchange              : {},
				baskets               : [],
			}
		},

		mounted: function(){
			this.getInfo();
		},

		computed: {},

		watch: {
			user_account: function(){
				this.getInfo();
			}
		},

		methods: {
			getInfo: function(){
				let vm = this;

				$.ajax({
					url     : 'user-accounts/' + vm.user_account.id,
					data    : {},
					type    : "get",
					dataType: "json",
					success : function(answer){
						vm.balances               = JSON.parse(JSON.stringify(answer.balances));
						vm.balancesWithoutBaskets = JSON.parse(JSON.stringify(answer.balances));
						vm.exchange               = answer.exchange;
						vm.baskets                = answer.baskets;

						vm.recalculateBalances();

						// чтобы показывалась подсказка к заголовку "Корзинки"
						Vue.nextTick(function(){
							$('[data-toggle="tooltip"]').tooltip()
						});
					},
					error   : function(e){
					}
				});
			},

			onBasketAdded: function(basket){
				this.baskets.push(basket);

				this.recalculateBalances();
			},

			recalculateBalances: function(){
				let vm        = this;
				let precision = 100000000;

				if (vm.baskets.length){
					let subtractionArray = {};

					this.baskets.forEach(function(basket){
						if (basket.next_action == 'buy'){
                            if (subtractionArray[basket.currency_pair.currency_2_code]) {
                                subtractionArray[basket.currency_pair.currency_2_code] = Number(subtractionArray[basket.currency_pair.currency_2_code]) + Number(basket.currency_2_last_amount);
							}
							else{
                                subtractionArray[basket.currency_pair.currency_2_code] = Number(basket.currency_2_last_amount);
							}
						}
						if (basket.next_action == 'sell'){
                            if (subtractionArray[basket.currency_pair.currency_1_code]) {
                                subtractionArray[basket.currency_pair.currency_1_code] = Number(subtractionArray[basket.currency_pair.currency_1_code]) + Number(basket.currency_1_last_amount ? basket.currency_1_last_amount : basket.start_sum);
							}
							else{
								// при создании корзинки, currency_1_last_amount ещё нет
                                subtractionArray[basket.currency_pair.currency_1_code] = Number(basket.currency_1_last_amount ? basket.currency_1_last_amount : basket.start_sum);
							}
						}
					});

					if (subtractionArray !== {}){
						vm.balances.splice(0); // очищаем балансы

						vm.balancesWithoutBaskets.forEach(function(balance, key){
							let newBalance = JSON.parse(JSON.stringify(balance));
							if (subtractionArray[balance.currency.code]){
								newBalance.amount = Math.round((Number(newBalance.amount) - Number(subtractionArray[newBalance.currency.code])) * precision) / precision;
							}
							vm.balances.push(newBalance);
						});
					}
				}
			},
			onBasketDeletion   : function(basketID){
				let vm = this;

				vm.baskets.forEach(function(basket, key){
					if (basket.id == basketID){
						vm.baskets.splice(key, 1);
					}
				});
			}
		},
	}
</script>
<style lang="scss">
	.exm-user-account{
		padding       : 20px 0px;
		margin        : 20px 0px;
		border-top    : 1px solid #cccccc;
		border-bottom : 1px solid #cccccc;
	}
	.account-balances{
		margin-top : 20px;
	}
	.account-balances__item{
		margin-top    : 10px;
		margin-bottom : 10px;
	}
	.account-baskets__item{
		margin-top    : 10px;
		margin-bottom : 10px;
	}
</style>