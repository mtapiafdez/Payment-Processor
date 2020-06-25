$(document).ready(() => {
    $("#card").focusout(elem => {
        const isNum = /^\d+$/.test(elem.target.value);
        if (!isNum) {
            elem.currentTarget.setCustomValidity("Invalid field.");
        } else {
            elem.currentTarget.setCustomValidity("");
        }
    });
});

const clearInputBoxes = () => {
    $("#payment-form").trigger("reset");
};

const processPayment = async event => {
    event.preventDefault();
    const firstName = $("#firstName").val();
    const lastName = $("#lastName").val();
    const email = $("#email").val();
    const charge = $("#charge").val();
    const card = $("#card").val();
    const expiration = $("#expiration").val();
    const cvc = $("#cvc").val();
    const reason = $("#reason").val();

    const response = await fetch("./api/processPayment.php?type=send", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            firstName,
            lastName,
            email,
            charge,
            card,
            expiration,
            cvc,
            reason
        })
    });

    const responseParsed = await response.json();

    if (responseParsed.type === "SUCCESS") {
        $("#message-fail").hide();
        $("#message-success").text(responseParsed.message);
        $("#message-success").show();
    } else {
        $("#message-success").hide();
        $("#message-fail").text(responseParsed.message);
        $("#message-fail").show();
    }
};

const switchForm = () => {
    const formHeader = $("#form-header");
    const formHeaderSub = $("#form-header-sub");
    const sectionProcessor = $("#section-processor");
    const sectionVerifier = $("#section-verifier");

    if (formHeader.text() === "Processor") {
        clearInputBoxes();
        refreshPaymentList();
        formHeader.text("Verifier");
        sectionProcessor.hide();
        sectionVerifier.show();
        formHeaderSub.text("Click 'Verifier' Above To Switch To Processor*");
        $("#message-success").hide();
        $("#message-fail").hide();
    } else {
        formHeader.text("Processor");
        sectionVerifier.hide();
        sectionProcessor.show();
        formHeaderSub.text("Click 'Processor' Above To Switch To Verifier*");
        clearVerifierValues();
    }
};

const verifyPayment = async () => {
    const paymentId = $("#verify-list").val();

    if (paymentId <= 0) {
        alert("There is no payment selected!");
        return;
    }

    const response = await fetch("./api/processPayment.php?type=receive", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            paymentId
        })
    });

    const responseParsed = await response.json();

    if (responseParsed.type === "SUCCESS") {
        const {
            payment_id,
            first_name,
            last_name,
            email,
            charge,
            card,
            expiration_date,
            cvc,
            reason,
            decryptedCard
        } = responseParsed.data.data;
        $("#vPaymentId").text(payment_id);
        $("#vEmail").text(email);
        $("#vFirstName").text(first_name);
        $("#vLastName").text(last_name);
        $("#vCharge").text(charge);
        $("#vReason").text(!reason ? "Reason Not Specified!" : reason);
        $("#vExp").text(expiration_date);
        $("#vCvc").text(cvc);
        $("#vEnc").text(card);
        $("#vDec").text(decryptedCard);
    } else {
        alert("There was an issue!");
    }
};

const refreshPaymentList = async () => {
    const response = await fetch("./api/processPayment.php?type=refresh", {
        method: "POST"
    });

    const responseParsed = await response.json();

    let html = `<option value="-5">Select Payment To Verify</option>`;

    responseParsed.forEach(record => {
        html += `
            <option value='${record.payment_id}'>Payment Id: ${record.payment_id} - ${record.first_name} ${record.last_name}</option>
        `;
    });

    $("#verify-list").html(html);
};

const clearVerifierValues = () => {
    $("#vPaymentId").text("");
    $("#vEmail").text("");
    $("#vFirstName").text("");
    $("#vLastName").text("");
    $("#vCharge").text("");
    $("#vReason").text("");
    $("#vExp").text("");
    $("#vCvc").text("");
    $("#vEnc").text("");
    $("#vDec").text("");
};
