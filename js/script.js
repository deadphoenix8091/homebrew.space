import './../scss/main.scss';

if (document.getElementById("icon"))


    document.getElementById("icon").onchange = function () {
        let iconLabelElement = document.getElementById("iconLabel");
        let iconWrapElement = document.querySelector('label[for="icon"]');
        iconLabelElement.value = this.files[0].name;

        if (!iconWrapElement.classList.contains('is-dirty')) {
            iconWrapElement.classList.add('is-dirty');
        }
    };

document.querySelector('input[name="search"]').onkeypress = function(e){
    if (!e) e = window.event;
    var keyCode = e.keyCode || e.which;
    if (keyCode == 13){
        var searchTerm = document.querySelector('input[name="search"]').value;
        if (searchTerm.length > 0) {
            window.location = '/search/' + encodeURI(searchTerm);
        }
        return false;
    }
};

(function() {
    'use strict';
    var projects = document.querySelectorAll('.project');
    var dialog = document.querySelector('#dialog');
    if (! dialog.showModal) {
        dialogPolyfill.registerDialog(dialog);
    }
    projects.forEach(function (currentProjectWrap) {
        console.log(currentProjectWrap);
        var dialogButton = currentProjectWrap.querySelector('.js-show-qr');
        var dialogContent = currentProjectWrap.querySelector(".dialog-content");
        var dialogContentContainer = dialog.querySelector(".mdl-dialog__content");
        dialogContentContainer.innerHTML = dialogContent.innerHTML;
        //mdl-dialog__content
        dialogButton.onclick = function() {
            dialog.showModal();
        };
    });

    dialog.querySelector('button:not([disabled])')
        .addEventListener('click', function() {
            dialog.close();
        });


}());