window.showMessage = function(message, title){
	if (!title){
		title = 'Сообщение';
	}

	let modal = '<div class="modal fade"' +
		'id="user-message"' +
		'tabindex="-1"' +
		'role="dialog"' +
		'aria-hidden="true">' +
		'	<div class="modal-dialog"' +
		'role="document">' +
		'	<div class="modal-content">' +
		'	<div class="modal-header">' +
		'	<h5 class="modal-title">' +
		title +
		'</h5>' +
		'<button type="button"' +
		'class="close"' +
		'data-dismiss="modal"' +
		'aria-label="Close">' +
		'	<span aria-hidden="true">&times;</span>' +
		'</button>' +
		'</div>' +
		'<div class="modal-body">' +
		message +
		'</div>' +
		' <div class="modal-footer">' +
		'        <button type="button" class="btn btn-primary" data-dismiss="modal">Ок</button>' +
		'      </div>' +
		'</div>' +
		'</div>' +
		'</div>';

	$('body').append(modal);

	$('#user-message').modal('show')
};