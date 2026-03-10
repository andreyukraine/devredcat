//----------------------- генерувати всі -----------------------------//

let temlateGpt;
let totalRequests = 0;
let completedRequests = 0;
let btnGenStore;
let btnGenStores;
let tapes;
let langs;
let key;
let model;

window.initGpt = function(elements, gpt_tmpl, tapes, langs, key, model){

  this.temlateGpt = gpt_tmpl;
  this.tapes = tapes;
  this.langs = langs;
  this.key = key;
  this.model = model;

  elements.each(function () {
    //тут перебираємо всі елементи які потрібно додати поле
    for (let j = 0; j < tapes.length; j++) {
      for (let i = 0; i < langs.length; i++) {
        if ($(this).attr("id") === tapes[j] + langs[i]) {
          if (document.getElementById(tapes[j] + langs[i])) {
            addGptLine(tapes[j], langs[i]);
          } else {
            console.error('Элемент с ID "' + tapes[j] + langs[i] + '" не найден.');
          }
        }
      }
    }
  });
}

function addGptLine(tape, lang){
  let inputGpt = document.createElement('textarea');
  inputGpt.setAttribute('type', 'text');
  inputGpt.setAttribute('id', 'gpt-template-' + tape + lang);
  inputGpt.setAttribute('name', tape + lang);
  inputGpt.setAttribute('class', 'gptp_in form-control');
  inputGpt.setAttribute('rows', '2');

  if (this.temlateGpt != null || this.temlateGpt !== undefined) {
    inputGpt.innerHTML = this.temlateGpt[lang][tape];
  }

  let block1 = document.createElement('div');
  block1.setAttribute('class', 'bl-gpt gpt-store-' + tape);
  block1.append(inputGpt);

  let btnGen = document.createElement('div');
  btnGen.setAttribute('class', 'line-btn-gpt');
  btnGen.setAttribute('id', 'btn-gen-' + tape + '-' + lang);
  btnGen.setAttribute('onclick', 'genItem(this, \'' + tape + lang + '\')');

  let btnImg = document.createElement('img');
  btnImg.setAttribute('class', 'icon-gpt-btn');
  btnImg.setAttribute('src', '/admin/view/image/chatgpt-w.png');
  btnGen.append(btnImg);

  let textNode = document.createTextNode('Згенерувати');
  btnGen.appendChild(textNode);
  block1.append(btnGen);

  let divIcon = document.createElement('img');
  divIcon.setAttribute('class', 'icon-gpt');
  divIcon.setAttribute('src', '/admin/view/image/chatgpt.png')
  block1.append(divIcon);

  setTimeout(() => {
    let blockInput = document.getElementById(tape + lang);
    if (blockInput) {
      blockInput.before(block1);
    } else {
      console.error('Элемент с ID "' + tape + lang + '" не найден.');
    }
  }, 200); // Задержка в 200 мс
}


function genItem(elem,id, lang){

  // Знаходимо батьківський елемент div з класом "bl-gpt"
  var parentDiv = elem.closest('.bl-gpt');

  // Шукаємо <input> всередині батьківського div
  var inputElement = parentDiv.querySelector('.gptp_in');

  // Отримуємо значення input
  var inputValue = inputElement.value;
  var inputName = inputElement.name;

  if (inputValue !== ""){

    elem.setAttribute('class', "line-btn-gpt-load");

    let block = $('#tab-general');
    let res_block = block.find('[id="' + inputName + '"]');
    sendGpt(inputValue, res_block, elem);
  }
}

function generateAll(elements){

    completedRequests = 0;

    elements.each(function () {

      let block = $('#tab-general');

      for (let j = 0; j < this.tapes.length; j++) {
        for (let i = 0; i < this.langs.length; i++) {

          let inputsGpt = block.find('#gpt-template-' + tapes[j] + langs[i]);

          inputsGpt.each(function (index, element) {
            if ($(element).val() !== "") {
              let resBlock = block.find('[id="' + $(element).attr('name') + '"]')
              if (resBlock.val() === "") {

                btnGenStore = document.getElementById("fastgen-gpt-store");
                btnGenStore.setAttribute('class', "btn-gpt-load");
                btnGenStores = document.getElementById("fastgen-gpt");
                btnGenStores.setAttribute('class', "btn-gpt-load");

                var parentDiv = this.closest('.bl-gpt');
                var btnGen = parentDiv.querySelector('#btn-gen-' + store_id + '-' + langs[i]);
                btnGen.setAttribute('class', "line-btn-gpt-load");

                totalRequests++; // Збільшуємо лічильник запитів

                sendGpt($(element).val(), resBlock, btnGen);
              }
            }

          });
        }
      }
    });
}

function sendGpt(query_text, block_input, elem){
  let result = false;

  let query_text_lang = "Please respond in ua-uk: " + query_text;

  let settings = {
    "url": "https://api.openai.com/v1/chat/completions",
    "method": "POST",
    "timeout": 0,
    "headers": {
      "Content-Type": "application/json",
      "Authorization": "Bearer " + this.key
    },

    "data": JSON.stringify({
      "model": this.model,
      "temperature": 1,
      "top_p": 1,
      "frequency_penalty": 0,
      "presence_penalty": 0,
      "messages": [
        {
          "role": "user",
          "content": query_text_lang
        }
      ]
    }),
  };

  $.ajax(settings).done(function (response) {
    completedRequests++;
    result = printText(elem, block_input, response.choices[0].message.content, 0);
  }).fail(function (xhr, status, error) {
    console.error(error);
    completedRequests++;
  });
  return result;
}

function printText(btnGen, block_input, text, index) {

  if (index < text.length) {
    // Додати наступний символ тексту до вхідного поля
    block_input.val(text.substring(0, index + 1));

    // Викликати цю ж функцію з наступним індексом через певну затримку (наприклад, 50 мс)
    setTimeout(function () {
      printText(btnGen, block_input, text, index + 1);
    }, 2);
  }else{

    if (completedRequests === totalRequests) {
      btnGenStores.setAttribute('class', "btn-gpt");
      btnGenStore.setAttribute('class', "btn-gpt");
      //alert("done");
    }

    btnGen.setAttribute('class', "line-btn-gpt");
    return true;
  }
}



//----------------------------------


function initGptDefault(elements, gpt_tmpl, tapes, langs){

  temlateGpt = gpt_tmpl;
  elements.each(function () {
    //тут перебираємо всі елементи які потрібно додати поле
    for (let j = 0; j < tapes.length; j++) {
      for (let i = 0; i < langs.length; i++) {
        addGptLineDefault(tapes[j], langs[i]);
      }
    }
  });
}

function addGptLineDefault(tape, lang){

  let templ_val = "";

  let inputGpt = document.createElement('input');
  inputGpt.setAttribute('type', 'text');
  inputGpt.setAttribute('id', 'gpt-template-' + tape + "-" + lang);
  inputGpt.setAttribute('name', tape + '-' + lang);
  inputGpt.setAttribute('class', 'gptp_in form-control');


  inputGpt.setAttribute('value', templ_val);

  let block1 = document.createElement('div');
  block1.setAttribute('class', 'bl-gpt gpt-store');
  block1.append(inputGpt);

  //--------------------- додаємо кнопки до полей GPT ---------------------------//
  let btnGen = document.createElement('div');
  btnGen.setAttribute('class', 'line-btn-gpt');
  btnGen.setAttribute('id', 'btn-gen-' + lang);
  btnGen.setAttribute('onclick', 'genItemDefault(this, ' + lang + ')');
  let btnImg = document.createElement('img');
  btnImg.setAttribute('class', 'icon-gpt-btn');
  btnImg.setAttribute('src', '/admin/view/image/chatgpt-w.png');
  btnGen.append(btnImg);
  // Додаємо текстовий вузол до div елементу
  let textNode = document.createTextNode('Згенерувати');
  btnGen.appendChild(textNode);
  block1.append(btnGen);

  //--------------------- додаємо icon GPT ---------------------------//
  let divIcon = document.createElement('img');
  divIcon.setAttribute('class', 'icon-gpt');
  divIcon.setAttribute('src', '/admin/view/image/chatgpt.png')
  block1.append(divIcon);

  let blockInput = document.getElementById(tape + '-' + lang);
  blockInput.before(block1);
}
