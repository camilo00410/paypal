
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Add meta tags for mobile and IE -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title> PayPal Checkout Integration | Server Demo </title>
    <link rel="stylesheet" href="{{ url('css/paypal.css') }}">
</head>

<body>

    <!-- Products -->
    <p>Select a product, then pay with PayPal</p>
    <input 
        type="radio" 
        id="product-1" 
        name="product" 
        onclick="handleClick(this)" 
        value="product-1"
    >
    <label for="product-1">Product 1 = $1.00</label>
    <br>
    <input 
        type="radio" 
        id="product-2"
        name="product" 
        onclick="handleClick(this)" 
        value="product-2"
    >
    <label for="product-2">Product 2 = $2.00</label>
    <br>
    <input 
        type="radio" 
        id="product-3"
        name="product" 
        onclick="handleClick(this)" 
        value="product-3"
    >
    <label for="product-3">Product 3 = $3.00</label>
    <br><br>

    <!-- Set up a container element for the button -->
    <div id="paypal-button-container"></div>

    <!-- Include the PayPal JavaScript SDK -->
    <script src="https://www.paypal.com/sdk/js?client-id=AV4vVxUfv4zad0iqcjcooep7nGYLbt_czCGgt-Mkow0kacaERVLK3IkPy9VT4LUD05UFBXmreHVMTEIH&currency=USD"></script>

    <script>

        // Find which radio button is clicked
        function handleClick(radioBtn){
            productValue = radioBtn.value;
            document.getElementById('paypal-button-container').style.display = 'block';
        }

        // Render the PayPal button into #paypal-button-container
        paypal.Buttons({

            // Call your server to set up the transaction
            createOrder: function(data, actions) {
                // return fetch('/demo/checkout/api/paypal/order/create/', {
                return fetch('/api/paypal/order/create/', {
                    method: 'post',
                    body: JSON.stringify({
                        "value":productValue
                    })
                }).then(function(res) {
                    return res.json();
                }).then(function(orderData) {
                    return orderData.id;
                });
            },

            // Call your server to finalize the transaction
            onApprove: function(data, actions) {
                // return fetch('/api/paypal/order/' + data.orderID + '/capture/', {
                return fetch('/api/paypal/order/capture/', {
                    method: 'post',
                    body:JSON.stringify({
                        "orderId": data.orderID
                    })
                }).then(function(res) {
                    return res.json();
                }).then(function(orderData) {
                    // Three cases to handle:
                    //   (1) Recoverable INSTRUMENT_DECLINED -> call actions.restart()
                    //   (2) Other non-recoverable errors -> Show a failure message
                    //   (3) Successful transaction -> Show confirmation or thank you

                    // This example reads a v2/checkout/orders capture response, propagated from the server
                    // You could use a different API or structure for your 'orderData'
                    var errorDetail = Array.isArray(orderData.details) && orderData.details[0];

                    if (errorDetail && errorDetail.issue === 'INSTRUMENT_DECLINED') {
                        return actions.restart(); // Recoverable state, per:
                        // https://developer.paypal.com/docs/checkout/integration-features/funding-failure/
                    }

                    if (errorDetail) {
                        var msg = 'Sorry, your transaction could not be processed.';
                        if (errorDetail.description) msg += '\n\n' + errorDetail.description;
                        if (orderData.debug_id) msg += ' (' + orderData.debug_id + ')';
                        return alert(msg); // Show a failure message (try to avoid alerts in production environments)
                    }

                    // Successful capture! For demo purposes:
                    console.log('Capture result', orderData, JSON.stringify(orderData, null, 2));
                    var transaction = orderData.purchase_units[0].payments.captures[0];
                    // alert('Transaction '+ transaction.status + ': ' + transaction.id + '\n\nSee console for all available details');

                    // Replace the above to show a success message within this page, e.g.
                    const element = document.getElementById('paypal-button-container');
                    element.innerHTML = '';
                    element.innerHTML = '<h3>Thank you for your payment!</h3>';
                    // Or go to another URL:  actions.redirect('thank_you.html');
                });
            }

        }).render('#paypal-button-container');
    </script>
</body>

</html>
    