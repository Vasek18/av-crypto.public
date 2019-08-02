<template>
	<form :action="action"
	      class="exm-connect-form"
	      method="post"
	      ref="form"
	      @submit.prevent="onSubmit"
	>
		<h2>Подключиться к {{ exchange_market.name }}</h2>
		<ul class="text-danger"
		    v-if="errors">
			<li v-for="error in errors">{{ error }}</li>
		</ul>
		<div class="form-group">
			<label for="api_key">Ключ апи</label>
			<input type="text"
			       id="api_key"
			       name="api_key"
			       ref="api_key"
			       class="form-control"
			       required
			>
		</div>
		<div class="form-group">
			<label for="secret_key">Секретный ключ апи</label>
			<input type="text"
			       id="secret_key"
			       name="secret_key"
			       ref="secret_key"
			       class="form-control"
			       required
			>
		</div>
		<div class="form-group">
			<button id="connect"
			        name="connect"
			        class="btn btn-success">Подключиться
			</button>
		</div>
	</form>
</template>
<script>
	export default {
		props: {
			exchange_market: {}
		},

		data: function(){
			return {
				errors: []
			}
		},

		mounted: function(){
		},

		computed: {
			action: function(){
				return 'exchange/' + this.exchange_market.id + '/connect';
			},
		},

		methods: {
			onSubmit: function(){
				let vm = this;

				vm.errors = [];

				$.ajax({
					url     : this.action,
					data    : {
						api_key           : vm.$refs.api_key.value,
						secret_key        : vm.$refs.secret_key.value,
						exchange_market_id: vm.exchange_market.id,
						_token            : csrf_token,
					},
					method  : "post",
					dataType: "json",
					success : function(answer){
						if (answer.account){
							vm.$emit('connected', answer.account);
						}
						else{
							if (answer.errors){
								vm.errors = answer.errors;
							}
							else{
								vm.errors.push('Ошибка подключения');
							}
						}
					},
					error   : function(e){
						vm.errors.push('Ошибка! Повторите попытку позже');
					}
				});
			}
		},
	}
</script>
<style lang="scss">
	.exm-connect-form{
		padding       : 20px 0px;
		margin        : 20px 0px;
		border-top    : 1px solid #cccccc;
		border-bottom : 1px solid #cccccc;
	}
</style>