<?php
include_once "./includes/header.php";
?>

<main class="container p-4">
    <h1 class="text-center h2 main-header">Payment <span id="form-header" onclick="switchForm();" class="text-danger form-switch">Processor</span>&nbsp;&nbsp;<i class="fas fa-credit-card text-dark"></i></h1>
    <p id="form-header-sub" class="text-center lead">Click 'Processor' Above To Switch To Verifier*</p>
    <div class="container__form">
        <hr />
        <section id="section-processor">
            <form id="payment-form" onsubmit="processPayment(event);" autocomplete="off">
                <div class="row">
                    <div class="col-sm-6 ">
                        <div class="form-group">
                            <label for="firstName">First Name</label>
                            <input maxlength="50" placeholder="Jon" id="firstName" name="firstName" type="text" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-sm-6 ">
                        <div class="form-group">
                            <label for="lastName">Last Name</label>
                            <input maxlength="50" placeholder="Doe" id="lastName" name="lastName" type="text" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-sm-6 ">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input maxlength="100" placeholder="jdoe@doe.com" id="email" name="email" type="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-sm-6 ">
                        <div class="form-group">
                            <label for="charge">Amount to Charge?</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input step=".01" min="0.00" placeholder="100.05" id="charge" name="charge" type="number" class="form-control" aria-label="Amount (dollar)" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="card">Card Number</label>
                            <input minlength="13" maxlength="19" placeholder="Ex: 5555555555554444" id="card" name="card" type="text" class="form-control" aria-label="Card Numbers Here" required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="expiration">Expiration - <small>Day does not matter*</small></label>
                            <input id="expiration" name="expiration" type="date" class="form-control" placeholder="2020-01-01" required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="cvc">CVC</label>
                            <input min="100" max="999" placeholder="123" id="cvc" name="cvc" type="number" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-12 mb-4">
                        <label for="reason">Reason - <small>Optional</small></label>
                        <textarea id="reason" name="reason" class="form-control textarea" maxlength="200"></textarea>
                    </div>
                    <div class="col-12 d-flex justify-content-between">
                        <button onclick="clearInputBoxes();" class="btn btn-danger">Clear</button>
                        <button name="submit-payment" type="submit" class="btn btn-dark">Process Payment</button>
                    </div>
                </div>
            </form>
            <div id="message-fail" class='mt-4 alert alert-danger notice' role='alert' style="display:none;">
                Oops! Something went wrong!
            </div>
            <div id="message-success" class='mt-4 alert alert-success notice' role='alert' style="display:none;">
                Success!
            </div>
        </section>
        <section id="section-verifier" style="display:none;">
            <div class="input-group mb-3">
                <select id="verify-list" name="verify-list" class="custom-select" required>
                </select>
                <div class="input-group-append">
                    <button onclick="verifyPayment();" class="btn btn-outline-dark" type="button">Verify</button>
                </div>
            </div>
            <div id="verify-data">
                <div class="row text-center">
                    <div class="col-sm-6 mb-4">
                        <h5>Payment Id:</h5><span class="lead" id="vPaymentId"></span>
                    </div>
                    <div class="col-sm-6 mb-4">
                        <h5>Email:</h5><span class="lead" id="vEmail"></span>
                    </div>
                    <div class="col-sm-6 mb-4">
                        <h5>First Name:</h5><span class="lead" id="vFirstName"></span>
                    </div>
                    <div class="col-sm-6 mb-4">
                        <h5>Last Name:</h5><span class="lead" id="vLastName"></span>
                    </div>
                    <div class="col-sm-6 mb-4">
                        <h5>Charge:</h5><span class="lead" id="vCharge"></span>
                    </div>
                    <div class="col-sm-6 mb-4">
                        <h5>Expiration / CVC:</h5><span class="lead" id="vExp"></span>&nbsp;&nbsp;<span class="lead" id="vCvc"></span>
                    </div>
                    <div class="col-sm-12 mb-4">
                        <h5>Reason:</h5><span class="lead" id="vReason"></span>
                    </div>
                    <div class="col-sm-12 mb-4">
                        <h5>Encrypted Card: (Stored In DB)</h5><span class="lead" id="vEnc"></span>
                    </div>
                    <div class="col-sm-12 mb-4">
                        <h5>Decrypted Card: (Computed From DB)</h5><span class="lead" id="vDec"></span>
                    </div>
                </div>
            </div>
            <h6 class="text-center lead mt-3"><strong>Note:</strong> If you come later and notice your payment is missing, it probably has something to do with the automatic deletion of payments after two weeks (db Trigger/Event). </h6>
            <h6 class="text-center lead mt-1">I certainly wouldn't include <span class="text-danger">Verifier</span> in a production app*</h6>
        </section>
    </div>
</main>

<?php
include_once "./includes/footer.php"
?>