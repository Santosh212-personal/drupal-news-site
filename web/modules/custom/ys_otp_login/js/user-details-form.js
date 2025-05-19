(function ($, Drupal) {
    $(document).ready(function () {
        $('#user-details-form').submit(function (event) {
            event.preventDefault();
  
            var uname = $('#name').val().trim();
            var uemail = $('#mail').val().trim();
  
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: { name: uname, mail: uemail},
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {                
                        $('#form-message').text(response.message).addClass('success'); 
                        setTimeout(function() {
                            $(location).prop('href', '/');
                        }, 3000); 
                    } else {
                        $('#form-message').text(response.message).addClass('error');
                    }
                },
                error: function () {
                    $('#form-message').text('An error occurred while updating your details.').addClass('error');
                }
            });
        });
    });
})(jQuery, Drupal);