document.getElementById("icon").onchange = function () {
    var iconLabelElement = document.getElementById("iconLabel");
    var iconWrapElement = document.querySelector('label[for="icon"]');
    iconLabelElement.value = this.files[0].name;

    if (!iconWrapElement.classList.contains('is-dirty')) {
        iconWrapElement.classList.add('is-dirty');
    }
};
