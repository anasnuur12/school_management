<script>
    window.onpageshow = function(event) {
        if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
            window.location.href = 'login.php'; // Redirect if back button is used
        }
    };
</script>
