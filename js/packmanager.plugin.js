/*
    Plugin do Jquery z funkcjami do MP 3.0
    @author Mateusz <mateusz@evl.pl>
 */
(function($)
{
    /*
        Komunikaty zależne od języka, są rozszerzane przez messages_{skrot_jezyka}.js
     */
    $.mp_messages = {

    }

    /*
       Funkcja do wyciągania komunikatów o błędach
    */
    $.fn.parseErrorMessage = function(message)
    {
        if(message !== undefined)
        {
            bootbox.alert('<section>' + message + '</section>');
        }
        else
        {
            bootbox.alert('<section>' + $.mp_messages['global_error'] + '</section>');
        }
    }

    bootbox.animate(false);

    $.fn.getModalWindow = function(message)
    {
        $('#modalWindow div.modal-body p').html(message);
        $('#modalWindow').modal();
    }

   /*
      Usuwanie pojedynczych rekordów
   */
    $.fn.addRemoveHandler = function()
    {
        /*
            Ustawiamy handler na ostatnim rekordzie
         */
        $('table tbody tr:last td a.del').click(function(){
            $(this).parent().parent().remove();
            $.fn.sumCosts();
        });
    }

    /*
        Pokazuje loader po zadanym elemencie
     */
    $.fn.showLoader = function(obj)
    {
       // $("#loader").show();
        obj.css('opacity', 0.3);
        $('body').css('cursor','wait');
    }

    /*
        Usuwa loader
     */
    $.fn.hideLoader = function(obj)
    {
       // $("#loader").hide();
        obj.css('opacity', 1);
        $('body').css('cursor','auto');

    }

    /*
        Metoda do walidacji dwóch dat
     */
    $.validator.addMethod("endDate", function(value, element) {
        var startDate = $('.startDate').val();
        return Date.parse(startDate) < Date.parse(value) || value == "";
    }, "");

    /*
        Metoda do walidacji aktualnej daty
     */
    $.validator.addMethod("startDate", function(value, element) {
        return new Date() > Date.parse(value) || value == "";
    }, "");


    /*
        Datepickery
     */
    $('.datepicker').datepicker({
        format: "dd.mm.yyyy"
    });

    /*
        Zaznaczanie aktywnej pozycji menu po kliknięciu
     */
    $('ul.nav-pills li a').click(function(){
        $(this).parent().addClass('active');
        return true;
    });

    /*
     Changer do adresu odbioru przesyłek
     */
    $('form.account-change input.cp-changer').change(function(){
        if($(this).is(':checked'))
        {
            $('form.account-change fieldset.cp').hide();
        }
        else
        {
            $('form.account-change fieldset.cp').show();
        }
    });

    if($('form.account-change input.cp-changer').is(':checked'))
    {
        $('form.account-change fieldset.cp').hide();
    }

    //dashboard

    $('a.open-dashboard').click(function(){
        if($('div.dashboard div.accordion').is(':hidden'))
            $('div.dashboard div.accordion').fadeIn('fast').show();
        else
            $('div.dashboard div.accordion').fadeOut('fast').hide();

    });

//    $('a.popover-btn').click(function(){
//        $.().popover({
//            html: $(this).next().html()
//        });
//    });

//    $('a.popover-btn').popover({
//        html: true,
//        content: $(this).next().html()
//    });

    $('a.popover-btn').popover({
        trigger: 'hover',
        html: true,
        placement: 'top',
        title: 'Hint',
        content: function(ele) { return $(this).next().html();}
    });

    $('a.hint').tooltip({
        trigger: 'hover',
        html: true,
        placement: 'top'
       // content: function(ele) { return $(this).attr('content');}
    });

    $('ul#simple_size label').tooltip({
        trigger: 'hover',
        html: true,
        placement: 'top'
        // content: function(ele) { return $(this).attr('content');}
    });


    $.validator.addMethod("money", function (value, element) {
        return this.optional(element) || value.match(/^\$?\d+(\.(\d{2}))?$/);
    }, "Please provide a valid pounds amount (up to 2 decimal places).");


    $.fn.sumCosts = function()
    {
        var floatSum = 0.00;
        var floatAccountBalance = parseFloat($('span.accountBalance').html());
        $('form.create-parcels table tr td.price p strong').each(function(){

            var floatVal = parseFloat($(this).html());
            floatSum += floatVal;
        });
        $('span.parcelsCost').html(floatSum);

//        if(floatSum > floatAccountBalance)
//            $('div.actions a.payment').attr('disabled', true);
//        else
//            $('div.actions a.payment').attr('disabled', false);
    }


    $.fn.findEmailAddresses = function(strObj)
    {
        //var arrLines = strObj.split("\n", strObj);
        var arrLines = strObj.replace(/\r\n/g, "\n").split("\n");

        var objReturn = {};
        var key = 0;
        for (var i in arrLines)
        {
            var arrEmails = arrLines[i].match(/([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9._-]+)/gi);
            if(arrEmails)
            {
                //tutaj będziemy szukać dodatkowych danych takich jak nr telefonu rozmiar paczki i paczkomat
                objReturn[key] = {};
                objReturn[key].recipient = arrEmails[0];
                objReturn[key].reference = arrLines[i].replace(arrEmails[0], '');

                //szukamy telefonu
                var arrTelephones = arrLines[i].match(/(?:\d{10,})/gi);
                if(arrTelephones && arrTelephones[0].length == 10)
                {
                    objReturn[key].telephone = arrTelephones[0];
                    objReturn[key].reference = objReturn[key].reference.replace(arrTelephones[0], '');
                }


                //szukamy rozmiaru paczek
                var arrPackSize = arrLines[i].match(/\s(S|M|L|s|m|l)\s/);
                if(arrPackSize)
                {
                    objReturn[key].packSize = arrPackSize[0].toUpperCase().trim();
                    objReturn[key].reference = objReturn[key].reference.replace(arrPackSize[0], '');
                }

                var arrTerminals = arrLines[i].match(/[A-Za-z]{5}\d{5}/gi);
                if(arrTerminals)
                {
                    objReturn[key].terminal = arrTerminals[0].toUpperCase().trim();
                    objReturn[key].reference = objReturn[key].reference.replace(arrTerminals[0], '');
                }
                else
                {
                    var arrTerminals = arrLines[i].match(/[A-Za-z]{5}\d{4}/gi);
                    if(arrTerminals)
                    {
                        objReturn[key].terminal = arrTerminals[0].toUpperCase().trim();
                        objReturn[key].reference = objReturn[key].reference.replace(arrTerminals[0], '');
                    }
                }

                objReturn[key].reference = objReturn[key].reference.trim();
                key++;
            }
        }
      //  console.log('objReturn', objReturn);
        return objReturn;
    }


})(jQuery);