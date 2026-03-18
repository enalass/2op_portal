"use strict";

// Class Definition
var KTLogin = function() {
    var _login;

    var _showForm = function(form) {
        var cls = 'login-' + form + '-on';
        var form = 'kt_login_' + form + '_form';

        _login.removeClass('login-forgot-on');
        _login.removeClass('login-signin-on');
        _login.removeClass('login-signup-on');

        _login.addClass(cls);

        KTUtil.animateClass(KTUtil.getById(form), 'animate__animated animate__backInUp');
    }

    var _handleSignInForm = function() {
        var validation;

        // Init form validation rules. For more info check the FormValidation plugin's official documentation:https://formvalidation.io/
        validation = FormValidation.formValidation(
			KTUtil.getById('kt_login_signin_form'),
			{
				fields: {
					username: {
						validators: {
							notEmpty: {
								message: 'Email is required'
							}
						}
					},
					password: {
						validators: {
							notEmpty: {
								message: 'Password is required'
							}
						}
					}
				},
				plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    submitButton: new FormValidation.plugins.SubmitButton(),
                    //defaultSubmit: new FormValidation.plugins.DefaultSubmit(), // Uncomment this line to enable normal button submit after form validation
					bootstrap: new FormValidation.plugins.Bootstrap()
				}
			}
		);

        $('#kt_login_signin_submit').on('click', function (e) {
            e.preventDefault();
            console.log("test");

            validation.validate().then(function(status) {
		        if (status == 'Valid') {
         //            swal.fire({
		       //          text: "All is cool! Now you submit this form.",
		       //          icon: "success",
		       //          buttonsStyling: false,
		       //          confirmButtonText: "Ok, got it!",
         //                customClass: {
    					// 	confirmButton: "btn font-weight-bold btn-light-primary"
    					// }
		       //      }).then(function() {
						KTUtil.scrollTop();
                        // alert($('#kt_login_signin_form').attr("action"));
                        $.ajax({
                            url: $('#kt_login_signin_form').attr("action"),  // ruta del controlador y accion
                            method: 'POST',
                            dataType: 'json',
                            data: $('#kt_login_signin_form').serialize(),     // Formulario
                            error: function()
                            {
                                alert("An error has occurred!");
                            },
                            success: function(response)
                            {          // Funcion que recibe response
                                
                                if (response.login_status == 'success'){
                                    window.location.href =response.redirect_url;
                                }else{
                                	$("input[name='" + response.token + "']").val(response.hash)
                                    swal.fire({
                                        // text: "All is cool! Now you submit this form.",
                                        icon: "error",
                                        buttonsStyling: false,
                                        confirmButtonText: response.msg,
                                        customClass: {
                                            confirmButton: "btn font-weight-bold btn-light-primary"
                                        }
                                    }).then(function() {
                                        KTUtil.scrollTop();
                                                });
                                }
                                
                            }
                        });
					// });
				} else {
					swal.fire({
		                text: "Sorry, an error seems to have occurred, please try again.",
		                icon: "error",
		                buttonsStyling: false,
		                confirmButtonText: "Ok, got it!",
                        customClass: {
    						confirmButton: "btn font-weight-bold btn-light-primary"
    					}
		            }).then(function() {
						KTUtil.scrollTop();
					});
				}
		    });
        });

        // Handle forgot button
        $('#kt_login_forgot').on('click', function (e) {
            e.preventDefault();
            _showForm('forgot');
        });

        // Handle signup
        $('#kt_login_signup').on('click', function (e) {
            e.preventDefault();
            _showForm('signup');
        });
    }

    var _handleSignUpForm = function(e) {
        var validation;
        var form = KTUtil.getById('kt_login_signup_form');

        // Init form validation rules. For more info check the FormValidation plugin's official documentation:https://formvalidation.io/
        validation = FormValidation.formValidation(
			form,
			{
				fields: {
					user: {
						validators: {
							notEmpty: {
								message: 'Usuario es obligatorio'
							}
						}
					},
					email: {
                        validators: {
							notEmpty: {
								message: 'Correo electrónico es obligatorio'
							},
                            emailAddress: {
								message: 'No contiene un correo electrónico válido'
							}
						}
					},
                    password: {
                        validators: {
                            notEmpty: {
                                message: 'Contraseña es obligatoria'
                            }
                        }
                    },
                    cpassword: {
                        validators: {
                            notEmpty: {
                                message: 'Confirmar contraseña es obligatoria'
                            },
                            identical: {
                                compare: function() {
                                    return form.querySelector('[name="password"]').value;
                                },
                                message: 'Las contraseñas no coinciden'
                            }
                        }
                    },
                    agree: {
                        validators: {
                            notEmpty: {
                                message: 'Debes aceptar los términos y condiciones'
                            }
                        }
                    },
				},
				plugins: {
					trigger: new FormValidation.plugins.Trigger(),
					bootstrap: new FormValidation.plugins.Bootstrap()
				}
			}
		);

        $('#kt_login_signup_submit').on('click', function (e) {
            e.preventDefault();

            validation.validate().then(function(status) {
		        if (status == 'Valid') {
                    swal.fire({
		                text: "¡Todo está ok! Ahora puedes enviar los datos",
		                icon: "success",
		                buttonsStyling: false,
		                confirmButtonText: "¡Sí, Envíalos!",
                        customClass: {
    						confirmButton: "btn font-weight-bold btn-light-primary"
    					}
		            }).then(function() {
						KTUtil.scrollTop();
					});
				} else {
					swal.fire({
		                text: "Lo sentimos, parece que hay algún error en tus datos, vuelte a intentarlo.",
		                icon: "error",
		                buttonsStyling: false,
		                confirmButtonText: "Ok, voy a ello",
                        customClass: {
    						confirmButton: "btn font-weight-bold btn-light-primary"
    					}
		            }).then(function() {
						KTUtil.scrollTop();
					});
				}
		    });
        });

        // Handle cancel button
        $('#kt_login_signup_cancel').on('click', function (e) {
            e.preventDefault();

            _showForm('signin');
        });
    }

    var _handleForgotForm = function(e) {
        var validation;

        // Init form validation rules. For more info check the FormValidation plugin's official documentation:https://formvalidation.io/
        validation = FormValidation.formValidation(
			KTUtil.getById('kt_login_forgot_form'),
			{
				fields: {
					email: {
						validators: {
							notEmpty: {
								message: 'Correo electrónico es obligatorio'
							},
                            emailAddress: {
								message: 'No contiene un correo electrónico válido'
							}
						}
					}
				},
				plugins: {
					trigger: new FormValidation.plugins.Trigger(),
					bootstrap: new FormValidation.plugins.Bootstrap()
				}
			}
		);

        // Handle submit button
        $('#kt_login_forgot_submit').on('click', function (e) {
            e.preventDefault();

            validation.validate().then(function(status) {
		        if (status == 'Valid') {
                    // Submit form
                    KTUtil.scrollTop();
				} else {
					swal.fire({
		                text: "Lo sentimos, parece que hay algún error, vuelve a intentarlo.",
		                icon: "error",
		                buttonsStyling: false,
		                confirmButtonText: "Ok, ¡Voy a ello!",
                        customClass: {
    						confirmButton: "btn font-weight-bold btn-light-primary"
    					}
		            }).then(function() {
						KTUtil.scrollTop();
					});
				}
		    });
        });

        // Handle cancel button
        $('#kt_login_forgot_cancel').on('click', function (e) {
            e.preventDefault();

            _showForm('signin');
        });
    }

    // Public Functions
    return {
        // public functions
        init: function() {
            _login = $('#kt_login');

            _handleSignInForm();
            _handleSignUpForm();
            _handleForgotForm();
        }
    };
}();

// Class Initialization
jQuery(document).ready(function() {
    KTLogin.init();
});
