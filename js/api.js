"use strict";
var load_card = function(){
	$.ajax({
		url : 'index.php',
		type : 'POST',
		data : {
			'task': "card_tmpl",
			'year': $('#year').val(),
			'month': $('#month').val(),
		},
		dataType: 'html',
		success : function(result, errors){
			$('#card_block').html(result);
		}
	})
};
var load_receipt = function(){
	$.ajax({
		url : 'index.php',
		type : 'POST',
		data : {
			'task': "receipt_tmpl",
			'year': $('#year').val(),
			'month': $('#month').val(),
			'card': $('#card_receipt').val(),
		},
		dataType: 'html',
		success : function(result, errors){
			$('#receipt_block').html($(result).find('#receipt_block').html());
			$('#receipt_balance').html($(result).find('#receipt_balance').html());
		}
	})
};
var load_spend = function(){
	$.ajax({
		url : 'index.php',
		type : 'POST',
		data : {
			'task': "spend_tmpl",
			'year': $('#year').val(),
			'month': $('#month').val(),
			'card': $('#card_spend').val(),
		},
		dataType: 'html',
		success : function(result, errors){
			$('#spend_block').html($(result).find('#spend_block').html());
			$('#spend_balance').html($(result).find('#spend_balance').html());
		}
	})
};

var load_transfer = function(){
	$.ajax({
		url : 'index.php',
		type : 'POST',
		data : {
			'task': "transfer_tmpl",
			'year': $('#year').val(),
			'month': $('#month').val(),
			'card1': $('#card1_transfer').val(),
			'card2': $('#card2_transfer').val(),
		},
		dataType: 'html',
		success : function(result, errors){
			$('#transfer_block').html(result);
		}
	})
};
var del = function(task, url){
	$.ajax({
		type: 'POST',
		url: url,
		data: form.serialize(),
		success : function(result, errors){
			load_card();
			window[task]();
		}
	});
}

$(function(){
	$.fn.autosubmit = function() {
		this.submit(function(event) {
			console.log('autosubmit');
			event.preventDefault();
			var form = $(this),
				type = parseInt(form.data('form')),
				task = 'load_' + $('input[name="task"]', form).val();
			$.ajax({
				type: form.attr('method'),
				url: form.attr('action'),
				data: form.serialize(),
				success : function(result, errors){
					load_card();
					window[task]();
				}
			});
		});
		return this;
	}
	$('form.ajax').autosubmit();
	$('body')
	.on('submit', '#cheque_form', function(){
		event.preventDefault();
		var form = $(this);
		var type = parseInt(form.data('form'));
		$.ajax({
			type: form.attr('method'),
			url: form.attr('action'),
			data: form.serialize(),
			dataType: 'html',
			success : function(result, errors){
				$('#cheque_block').html($(result).html());
			}
		});
	})
	.on('click', '#update', function(){
		load_card();
		load_receipt();
		load_spend();
		load_transfer();
	})
	.on('click', '#update_receipt', function(){
		load_receipt();
	})
	.on('click', '#update_spend', function(){
		load_spend();
	})
	.on('click', '#update_transfer', function(){
		load_transfer();
	})
	.on('click', '.del_receipt', function(){
		del('load_receipt', $(this).attr('href'));
		return false;
	})
	;

	load_card();
	load_receipt();
	load_spend();
	load_transfer();
})
