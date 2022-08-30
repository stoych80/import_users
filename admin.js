jQuery(function ($) {
	
	$('#dd_import_users_fields_profiles').change(function (e) {
		if ($(this).val()=='') $('#buttons-wrapper1').slideUp();
		else $('#buttons-wrapper1').slideDown();
	});
	$('.dd_import_users-load-profile').click(function (e) {
		e.preventDefault();
		$('<div></div>').appendTo('body').html('<div class="dialog-errs" style="color:red;"></div><div style="margin-top:10px;margin-bottom:10px;">Are you sure? The profile "'+$('#dd_import_users_fields_profiles').val()+'" will replace the values in the current fields. <span id="spinner"></span></div>').dialog({
			modal: true,
			title: 'Confirm',
			zIndex: 10000,
			autoOpen: true,
			width: '70%',
			resizable: false,
			buttons: {
				Yes: function () {
					$('#spinner').html(' <img id="dd_import_users_save-fields_run_spinner" src="/wp-admin/images/spinner.gif" width="20" height="20" border="0" align="middle" />');
					var the_dialog = $(this);
					$.ajax({
						type:"POST",
						url: "/wp-admin/users.php?page=dd_import_users&load_fields_profile=1&profile_name="+encodeURIComponent($('#dd_import_users_fields_profiles').val()),
						dataType:"json",
						data:{},
						success: function(data) {
							$('#dd_import_users_save-fields_run_spinner').remove();
							if(typeof data!='object') {
								$('.dialog-errs').html(data);
							} else {
								for (var i in data) {
									$('input[name="'+i+'"]').val(data[i]);
								}
								the_dialog.dialog("close");
								the_dialog.dialog('destroy').remove();
							}
						}
					});
				},
				No: function () {
					$(this).dialog("close");
					$(this).dialog('destroy').remove();
				}
			},
			closeOnEscape: true,
			open: function(ev, ui) { $(".ui-dialog-titlebar-close", ui.dialog | ui).hide();}
		});
	});
	$('.dd_import_users-update-fields-profile').click(function (e) {
		e.preventDefault();
		$('<div></div>').appendTo('body').html('<div class="dialog-errs" style="color:red;"></div><div style="margin-top:10px;margin-bottom:10px;">Profile name <span style="color:red;">*</span> <i>(only space, letters and numbers)</i>: <input type="text" id="profile_name" style="width:60%;" value="'+$('#dd_import_users_fields_profiles').val()+'" /><input type="hidden" id="old_profile_name" value="'+$('#dd_import_users_fields_profiles').val()+'" /><br>The profile will be updated with the values of the current fields. <span id="spinner"></span></div>').dialog({
			modal: true,
			title: 'Update Fields Profile',
			zIndex: 10000,
			autoOpen: true,
			width: '70%',
			resizable: false,
			buttons: {
				Update: function () {
					$('#profile_name').after(' <img id="dd_import_users_save-fields_run_spinner" src="/wp-admin/images/spinner.gif" width="20" height="20" border="0" align="middle" />');
					var the_dialog = $(this);
					$.ajax({
						type:"POST",
						url: "/wp-admin/users.php?page=dd_import_users&update_fields_profile=1&profile_name="+encodeURIComponent($('#profile_name').val())+'&old_profile_name='+encodeURIComponent($('#old_profile_name').val()),
						dataType:"text",
						data:$(".dd_import_users-admin-form").serialize(),
						success: function(data) {
							$('#dd_import_users_save-fields_run_spinner').remove();
							if(data=='success') {
								if ($('#profile_name').val() != $('#old_profile_name').val()) {
									$("#dd_import_users_fields_profiles option[value='"+$('#old_profile_name').val()+"']").remove();
									$('#dd_import_users_fields_profiles').append($('<option>', {
										value: $('#profile_name').val(),
										text: $('#profile_name').val()
									}));
									$('#dd_import_users_fields_profiles').val($('#profile_name').val());
								}
								the_dialog.dialog("close");
								the_dialog.dialog('destroy').remove();
							} else {
								$('.dialog-errs').html(data);
							}
						}
					});
				},
				Close: function () {
					$(this).dialog("close");
					$(this).dialog('destroy').remove();
				}
			},
			closeOnEscape: true,
			open: function(ev, ui) { $(".ui-dialog-titlebar-close", ui.dialog | ui).hide();}
		});
	});
	$('.dd_import_users-create-fields-profile').click(function (e) {
		e.preventDefault();
		$('<div></div>').appendTo('body').html('<div class="dialog-errs" style="color:red;"></div><div style="margin-top:10px;margin-bottom:10px;">Profile name <span style="color:red;">*</span> <i>(only space, letters and numbers)</i>: <input type="text" id="profile_name" style="width:60%;" /></div>').dialog({
			modal: true,
			title: 'Create Fields Profile',
			zIndex: 10000,
			autoOpen: true,
			width: '70%',
			resizable: false,
			buttons: {
				Create: function () {
					$('#profile_name').after(' <img id="dd_import_users_save-fields_run_spinner" src="/wp-admin/images/spinner.gif" width="20" height="20" border="0" align="middle" />');
					var the_dialog = $(this);
					$.ajax({
						type:"POST",
						url: "/wp-admin/users.php?page=dd_import_users&create_fields_profile=1&profile_name="+encodeURIComponent($('#profile_name').val()),
						dataType:"text",
						data:$(".dd_import_users-admin-form").serialize(),
						success: function(data) {
							$('#dd_import_users_save-fields_run_spinner').remove();
							if(data=='success') {
								$('#dd_import_users_fields_profiles').append($('<option>', {
									value: $('#profile_name').val(),
									text: $('#profile_name').val()
								}));
								$('#dd_import_users_fields_profiles').val($('#profile_name').val());
								$('#buttons-wrapper1').slideDown();
								the_dialog.dialog("close");
								the_dialog.dialog('destroy').remove();
							} else {
								$('.dialog-errs').html(data);
							}
						}
					});
				},
				Close: function () {
					$(this).dialog("close");
					$(this).dialog('destroy').remove();
				}
			},
			closeOnEscape: true,
			open: function(ev, ui) { $(".ui-dialog-titlebar-close", ui.dialog | ui).hide();}
		});
	});
	$('.dd_import_users-delete-fields-profile').click(function (e) {
		e.preventDefault();
		$('<div></div>').appendTo('body').html('<div class="dialog-errs" style="color:red;"></div><div style="margin-top:10px;margin-bottom:10px;">The Profile "'+$('#dd_import_users_fields_profiles').val()+'" will be deleted? <span id="spinner"></span></div>').dialog({
			modal: true,
			title: 'Confirm',
			zIndex: 10000,
			autoOpen: true,
			width: '70%',
			resizable: false,
			buttons: {
				Confirm: function () {
					$('#spinner').html(' <img id="dd_import_users_save-fields_run_spinner" src="/wp-admin/images/spinner.gif" width="20" height="20" border="0" align="middle" />');
					var the_dialog = $(this);
					$.ajax({
						type:"POST",
						url: "/wp-admin/users.php?page=dd_import_users&delete_fields_profile=1&profile_name="+encodeURIComponent($('#dd_import_users_fields_profiles').val()),
						dataType:"text",
						data:{},
						success: function(data) {
							$('#dd_import_users_save-fields_run_spinner').remove();
							if(data=='success') {
								$("#dd_import_users_fields_profiles option[value='"+$('#dd_import_users_fields_profiles').val()+"']").remove();
								$('#dd_import_users_fields_profiles').val('');
								$('#buttons-wrapper1').slideUp();
								the_dialog.dialog("close");
								the_dialog.dialog('destroy').remove();
							} else {
								$('.dialog-errs').html(data);
							}
						}
					});
				},
				Close: function () {
					$(this).dialog("close");
					$(this).dialog('destroy').remove();
				}
			},
			closeOnEscape: true,
			open: function(ev, ui) { $(".ui-dialog-titlebar-close", ui.dialog | ui).hide();}
		});
	});
	
	
	$('.dd_import_users-large-button').click(function (e) {
		if ($('input[name="step"]').val()==1 && !$('input[name="select_the_previously_uploaded_file"]').is(':checked')) {
			var msg;
			if ($('#dd_import_users-url-upload-status').html() === '') {
				msg = 'Please select CSV file to upload';
			} else if ($('#dd_import_users-url-upload-status').html() === '<span style="color:red;">The Uploaded file must be CSV</span>') {
				msg = 'The Uploaded file must be CSV';
			}
			if (msg) {
				e.preventDefault();
				$('<div></div>').appendTo('body').html('<div>'+msg+'</div>').dialog({
					modal: true,
					title: 'Error',
					zIndex: 10000,
					autoOpen: true,
					width: '50%',
					resizable: false,
					buttons: {
						Ok: function () {
							$(this).dialog("close");
							$(this).dialog('destroy').remove();
						}
					},
					closeOnEscape: true,
					open: function(ev, ui) { $(".ui-dialog-titlebar-close", ui.dialog | ui).hide();}
				});
			}
		} else if ($('input[name="step"]').val()==2) {
			e.preventDefault();
			if ($('#process_records_per_query').val()<2) {
				$('<div></div>').appendTo('body').html('<div>Please specify at lest 2 "Process records per query".</div>').dialog({
					modal: true,
					title: 'Error',
					zIndex: 10000,
					autoOpen: true,
					width: '70%',
					resizable: false,
					buttons: {
						Ok: function () {
							$(this).dialog("close");
							$(this).dialog('destroy').remove();
						}
					},
					closeOnEscape: true,
					open: function(ev, ui) { $(".ui-dialog-titlebar-close", ui.dialog | ui).hide();}
				});
				return;
			}
			$('<div></div>').appendTo('body').html('<div>Do you want to run the import? It is in '+($('#simulation').is(":checked") ? '<span style="background-color:yellow;">Simulation' : '<span style="color:red;">Live')+'</span> mode.</div>').dialog({
				modal: true,
				title: 'Confirm',
				zIndex: 10000,
				autoOpen: true,
				width: '50%',
				resizable: false,
//				position: {my:'top',at:'top+190'},
				buttons: {
					Yes: function () {
						$(this).dialog("close");
						$(this).dialog('destroy').remove();
						$('#dd_import_users_run_spinner').show();
						$('#progressbardd_import_users').show();
						setTimeout(function(){
							recursively_ajax(2);
						}, 1000);
					},
					No: function () {
						$(this).dialog("close");
						$(this).dialog('destroy').remove();
					}
				},
				closeOnEscape: true,
				open: function(ev, ui) { $(".ui-dialog-titlebar-close", ui.dialog | ui).hide();}
			});
		}
	});
	function recursively_ajax(iterate_start) {
		var process_records_per_query = $('#process_records_per_query').val();
		var progressbardd_import_users = $("#progressbardd_import_users"), progressLabeldd_import_users = $(".progress-labeldd_import_users");
		var init_val = $('#count_csv_records').val()>0 ? ((iterate_start>process_records_per_query ? iterate_start-process_records_per_query : 0)/$('#count_csv_records').val())*100 : 0;
		init_val = Math.ceil(init_val);
		var completeness = $('#count_csv_records').val()>0 ? ((iterate_start)/$('#count_csv_records').val())*100 : 100;
		progressbardd_import_users.progressbar({
			value:init_val,
			change: function() {
				progressLabeldd_import_users.html("<b>"+progressbardd_import_users.progressbar("value") + "%</b>");
			},
			complete: function() {
				//progressLabeldd_import_users.html("Complete!");
			}
		});
		$.fn.progressdd_import_users = function (profile_completeness) {
			var val = progressbardd_import_users.progressbar("value") || 0;
			var move_forward = profile_completeness<1 || profile_completeness>val || profile_completeness>=100;
			if (move_forward) {
				progressbardd_import_users.progressbar("value", val+1);
				if (val+1 < profile_completeness) {
					setTimeout(function () {$.fn.progressdd_import_users(profile_completeness)}, 20);
				} else if (val+1 < 100) {
//					$("#progressbardd_import_users").after(\''.esc_js($profile_completeness_text_if_incomplete).'\');
				}
			} else {
				progressbardd_import_users.progressbar("value", val-1);
				if (val-1 > profile_completeness)
				  setTimeout(function () {$.fn.progressdd_import_users(profile_completeness)}, 20);
			}
		}
		setTimeout(function () {$.fn.progressdd_import_users(completeness)},300);
		$.ajax({
			type:"POST",
//			async:false, // set async false to wait for previous response
			url: "/wp-admin/users.php?page=dd_import_users&iterate_start="+iterate_start+'&process_records_per_query='+process_records_per_query,
			dataType:"text",
			data:$(".dd_import_users-admin-form").serialize(),
			success: function(data) {
				if(data=='dd_import_users_next_iterate') {
					setTimeout(function(){
						recursively_ajax(Number(iterate_start)+Number(process_records_per_query));
					}, 2000);
				} else {
					$('#dd_import_users_run_spinner').hide();
					$('<div></div>').appendTo('body').html('<div>'+data+'</div>').dialog({
						modal: true,
						title: 'Notice',
						zIndex: 10000,
						autoOpen: true,
						width: '70%',
						resizable: true,
//						position: {my:'top',at:'top+190'},
						buttons: {
							Ok: function () {
								$(this).dialog("close");
								$(this).dialog('destroy').remove();
							}
						},
						closeOnEscape: true,
						open: function(ev, ui) { $(".ui-dialog-titlebar-close", ui.dialog | ui).hide();}
					});
				}
			}
		});
	}
	$('a.dd_import_users-import-from.dd_import_users-upload-type').click(function (e) {
		$(this).addClass('selected');
		$('input[type="file"]').trigger('click');
	});
	$('input[type="file"]').change(function (e) {
		var val = $(this).val(), filename, ext;
		if (!val) {
			$('#dd_import_users-url-upload-status').html('');
			return;
		}
		filename = val.split('\\');
		filename = filename[filename.length-1];
		ext = val.split('.');
		ext = ext[ext.length-1];
		if (ext!='csv') {
			$('#dd_import_users-url-upload-status').html('<span style="color:red;">The Uploaded file must be CSV</span>');
		} else $('#dd_import_users-url-upload-status').html(filename);
	});
	$('.dd-import-users-sticky ul li.csv_header_title a').click(function (e) {
		e.preventDefault();
	});
	$('.dd-import-users-sticky ul li.csv_header_title a').draggable({
		revert: 'invalid',
		appendTo: 'body',
		containment: 'window',
		scroll: false,
		helper: function(event) {
			return $(event.target).clone().css({
				width: $(event.target).width()+30,
				'font-size': '135%',
				'color': 'green'
			});
		}
	});
	if ($('.dd_import_users-user_fields ul li input[type="text"]').length)
	$('.dd_import_users-user_fields ul li input[type="text"].dropit').droppable({
		hoverClass: 'active',
		drop: function (event, ui) {
			if (this.name=='PmPro Level' || this.name=='Membership Start Date' || this.name=='Membership Expiry Date') {
				if (this.value) {
					$('<div></div>').appendTo('body').html('<div style="margin-top:10px;margin-bottom:10px;">The PmPro fields can have only single value.</div>').dialog({
						modal: true,
						title: 'PmPro Only Single value',
						zIndex: 10000,
						autoOpen: true,
						width: '70%',
						resizable: false,
						buttons: {
							Ok: function () {
								$(this).dialog("close");
								$(this).dialog('destroy').remove();
							}
						},
						closeOnEscape: true
					});
				}
				this.value = $(ui.draggable).text();
			} else {
				this.value += this.value ? ', ' : '';
				this.value += $(ui.draggable).text();
			}
		}
	});
	$(window).scroll(function() {
		var sticky = $('.dd-import-users-sticky'),
			scroll = $(window).scrollTop();

		if (scroll >= 280) sticky.addClass('dd-import-users-fixed');
		else sticky.removeClass('dd-import-users-fixed');
	});
	if ($('.dd_import_users-user_fields ul li input[type="text"]+img').length)
	$('.dd_import_users-user_fields ul li input[type="text"]+img').tooltip({
      show: {
        effect: "slideDown",
        delay: 250
      }
    });
});