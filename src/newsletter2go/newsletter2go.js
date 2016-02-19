function n2goPreviewRendered() {
    document.getElementById('preview-loading-mask').style.display = 'none';
}

window.onload = function (e) {
    var dragSrcEl = null,
            farb = jQuery.farbtastic('#colorPicker'),
            elements = document.getElementsByClassName('js-n2go-widget-field'),
            i,
            renderHTML = function () {
                var widget = document.getElementById('widgetSourceCode'),
                        view = document.getElementById('widgetPreview');
                document.getElementById('preview-loading-mask').style.display = 'block';
                widget.style.display = 'none';
                view.src = document.getElementById('widgetPageUrl').value + '?widget=' + encodeURIComponent(widget.value);
                view.style.display = 'block';

                document.getElementById('btnShowPreview').className = 'form-submit button-pressed';
                document.getElementById('btnShowSource').className = 'form-submit';
            };

    function buildWidgetForm(rebuildForm) {
        var sourceCode = '';
        if (rebuildForm) {
            var checkBoxes = document.getElementsByClassName('form-checkbox'),
                    fields = [], i, elem,
                    texts, styles, inputStyle = '';

            for (i = 0; i < checkBoxes.length; i++) {
                if (checkBoxes[i].checked === true) {
                    elem = [];
                    elem['sort'] = document.getElementsByName(checkBoxes[i].value + 'Sort')[0].value;
                    elem['required'] = document.getElementsByName(checkBoxes[i].value + 'Required')[0].value;
                    elem['name'] = checkBoxes[i].title;
                    elem['id'] = checkBoxes[i].value;

                    fields.push(elem);
                }
            }

            texts = [];
            texts['button'] = document.getElementById('edit-widget-texts-buttontext').value;

            styles = [];
            styles['textColor'] = document.getElementById('edit-widget-colors-textcolor').value;
            styles['borderColor'] = document.getElementById('edit-widget-colors-bordercolor').value;
            styles['backgroundColor'] = document.getElementById('edit-widget-colors-bgcolor').value;
            styles['btnTextColor'] = document.getElementById('edit-widget-colors-btntextcolor').value;
            styles['btnBackgroundColor'] = document.getElementById('edit-widget-colors-btnbgcolor').value;

            fields.sort(function (a, b) {
                return a['sort'] - b['sort'];
            });

            sourceCode = '<div id="n2goResponseArea" ' + (styles['textColor'] ? 'style="color:' + styles['textColor'] + '"' : '') + '>';
            sourceCode += '\n  <form method="post" id="n2goForm">';

            if (styles['borderColor'] || styles['backgroundColor'] || styles['textColor']) {
                inputStyle = 'style="';
                inputStyle += styles['borderColor'] ? 'border-color:' + styles['borderColor'] + '; ' : '';
                inputStyle += styles['backgroundColor'] ? 'background-color:' + styles['backgroundColor'] + '; ' : '';
                inputStyle += styles['textColor'] ? 'color:' + styles['textColor'] + '; ' : '';
                inputStyle += '" ';
            }

            for (i = 0; i < fields.length; i++) {
                if (fields[i]['name'] === 'Gender') {
                    sourceCode += '\n    ' + fields[i]['name'] + '<br />\n    ' + '<select ' + inputStyle + 'name="' + fields[i]['id'] + '" ' + fields[i]['required'] + '>';
                    sourceCode += '\n      <option value=" "></option>';
                    sourceCode += '\n      <option value="m">Male</option>';
                    sourceCode += '\n      <option value="f">Female</option>';
                    sourceCode += '\n    </select><br>';
                } else {
                    sourceCode += '\n    ' + fields[i]['name'] + '<br />\n    ' + '<input ' + inputStyle + 'type="text" name="' + fields[i]['id'] + '"' +  fields[i]['required'] + ' /><br />';
                }
            }

            sourceCode += '\n    <br />\n    <div class="message"></div>';
            sourceCode += '\n    <input ';
            if (styles['btnTextColor'] || styles['btnBackgroundColor']) {
                sourceCode += 'style="';
                sourceCode += styles['btnTextColor'] ? 'color:' + styles['btnTextColor'] + ';' : '';
                sourceCode += styles['btnBackgroundColor'] ? 'background-color:' + styles['btnBackgroundColor'] + ';' : '';
                sourceCode += '"';
            }

            sourceCode += ' id="n2goButton" type="button" value="' + texts['button'] + '" onClick="n2goAjaxFormSubmit();" class="form-submit" />\n  </form>\n</div>';
            document.getElementById('widgetSourceCode').innerHTML = sourceCode;
            document.getElementById('widgetSourceCode').value = sourceCode;
        }

        renderHTML();
    }

    function extractValues(elem) {
        return {
            nameSort: elem.children[1].name,
            nameRequired: elem.children[2].name,
            valueRequired: elem.children[2].value,
            id: elem.children[0].children[0].id,
            name: elem.children[0].children[0].name,
            value: elem.children[0].children[0].value,
            title: elem.children[0].children[0].title,
            checked: elem.children[0].children[0].checked,
            disabled: elem.children[0].children[0].disabled,
            label: elem.children[0].children[1].innerHTML
        };
    }

    function importValues(elem, values) {
        elem.children[1].name = values.nameSort;
        elem.children[2].name = values.nameRequired;
        elem.children[2].value = values.valueRequired;
        elem.children[0].children[0].id = values.id;
        elem.children[0].children[0].name = values.name;
        elem.children[0].children[1].htmlFor = values.id;
        elem.children[0].children[0].value = values.value;
        elem.children[0].children[0].title = values.title;
        elem.children[0].children[1].innerHTML = values.label;
        elem.children[0].children[0].checked = values.checked;
        elem.children[0].children[0].disabled = values.disabled;
    }

    function handleDragStart(e) {
        dragSrcEl = this;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('Text', JSON.stringify(extractValues(this)));
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        return false;
    }

    function handleDragEnter(e) {
        e.preventDefault();
        this.classList.add('over');
    }

    function handleDragLeave(e) {
        e.preventDefault();
        this.classList.remove('over');
    }

    function handleDrop(e) {
        e.stopPropagation();
        e.preventDefault();

        if (dragSrcEl !== this) {
            var a = JSON.parse(e.dataTransfer.getData('Text'));
            var b = extractValues(this);
            importValues(dragSrcEl, b);
            importValues(this, a);
        }

        return false;
    }

    function handleDragEnd(e) {
        [].forEach.call(document.querySelectorAll('#widgetFields .widgetField'), function (field) {
            field.classList.remove('over');
        });

        buildWidgetForm(true);
    }

    [].forEach.call(document.querySelectorAll('#widgetFields .widgetField'), function (field) {
        field.addEventListener('dragstart', handleDragStart, false);
        field.addEventListener('dragenter', handleDragEnter, false);
        field.addEventListener('dragover', handleDragOver, false);
        field.addEventListener('dragleave', handleDragLeave, false);
        field.addEventListener('drop', handleDrop, false);
        field.addEventListener('dragend', handleDragEnd, false);
    });

    buildWidgetForm(true);

    document.getElementById('btnShowSource').onclick = function () {
        var view = document.getElementById('widgetSourceCode');
        view.style.display = 'block';
        document.getElementById('widgetPreview').style.display = 'none';
        this.className = 'form-submit button-pressed';
        document.getElementById('btnShowPreview').className = 'form-submit';
    };

    document.getElementById('btnShowPreview').onclick = function(){
        renderHTML();
    };

    function hookClickHandler(checkbox) {
        checkbox.onclick = function (e) {
            var hiddenReq = this.parentElement.parentElement.children[2];
            if (!this.checked) {
                if (hiddenReq.value === 'required') {
                    e.preventDefault();
                    this.checked = true;
                    hiddenReq.value = '';
                    this.nextElementSibling.innerHTML = this.title;
                    buildWidgetForm();

                    return false;
                }
            } else {
                hiddenReq.value = 'required';
                this.nextElementSibling.innerHTML += ' <span class="form-required n2go-required" title="This field is required.">*</span>';
            }
        };
    }

    for (i = 0; i < elements.length; i++) {
        if (elements[i].type === 'checkbox') {
            hookClickHandler(elements[i]);
        }

        elements[i].onchange = function () {
            buildWidgetForm(true);
        };
    }

    jQuery('.color-picker').focus(function() {
        var input = this;

        // reset to start position before linking to current input
        farb.linkTo(function(){}).setColor('#000');
        farb.linkTo(function (color) {
            input.style.backgroundColor = color;
            input.style.color = farb.RGBToHSL(farb.unpack(color))[2] > 0.5 ? '#000' : '#fff';
            input.value = color;
        }).setColor(input.value);
    }).blur(function () {
        farb.linkTo(function(){}).setColor('#000');
        if (!this.value) {
            this.style.backgroundColor = '';
            this.style.color = '';
        }

        buildWidgetForm(true);
    });
};