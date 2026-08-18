$(document).ready(function () {
    $('.toggle-password').on('click', function () {
        // Toggle the tabler icon classes
        $(this).find('i').toggleClass('ti-eye ti-eye-off');
        
        // Find the input element
        var input = $($(this).attr('data-toggle'));
        
        // Toggle password visibility
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
        } else {
            input.attr('type', 'password');
        }
    });
});