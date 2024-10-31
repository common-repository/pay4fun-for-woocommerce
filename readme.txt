=== Pay4Fun for WooCommerce ===

Contributors: felipematosmoreira
Authors: [Pay4Fun](https://p4f.com)
Tags: pay4fun, p4f, payment, gateway, payment-gateway, woocommerce, donations
Requires at least: 4.7
Tested up to: 5.5
Stable tag: 1.0.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html



== Description ==

Plugin to add payment options to WooCommerce using **Pay4Fun** payment method.

**Pay4Fun** is an online payment platform that allows you to execute transactions in a practical and secure way.
 
We offer a last generation e-wallet where payments and fund transfers to licensed websites are realized in a simple way, without bureaucracy.
 
This is our mission: to facilitate the day-to-day of both consumers and merchants through a simple and intuitive platform, giving excellent service on all levels.
For more information, please visit: https://p4f.com  
Before installing the WooCommerce plugin, follow the steps below to make your experience as smooth, fast and efficient as possible:
1. Send an email to merchant@p4f.com stating: name/registration of the company that will operate the e-commerce, name/ID of the person in charge, URL of the e-commerce, main products and services marketed and details of the contact person (email and phone)
2. Pay4Fun will respond by requesting the necessary documents for the KYC (Know Your Client) process and commercial proposal.
3. Once everyone is in agreement, just sign the contract. Then you will have access to the Back Office where we provide the necessary credentials for installing the plugin. This Back Office will also be the environment to monitor all your transactions, balance, etc.
Important:
1. The plugin does not allow the reversal of transactions to customers (pay-out). Pay-out is possible via the Back Office.
2. If you are already a ** Pay4Fun ** merchant through the conventional API, DO NOT use the credentials of that API when installing the plugin. To use the WooCommerce plugin from your existing merchant account, please contact merchant@p4f.com requesting the activation of your merchant account for the plugin.
3. Minimum and maximum amounts per transaction are defined by the merchant.
4. We accept payments in BRL, USD, EUR and GBP. The merchant account is always in BRL.
5. Languages: Portuguese, English and Spanish.

Agradecemos seu interesse em utilizar a Pay4Fun como método de pagamento em seu e-commerce!
A Pay4Fun é uma plataforma de pagamentos on-line que permite você realizar transações de forma prática e segura.
Oferecemos uma carteira virtual (e-wallet) de última geração, onde pagamentos e transferências para os sites credenciados são realizados de forma simples e sem burocracias.
Essa é a nossa missão: facilitar o dia a dia de consumidores e comerciantes (merchants) através de uma plataforma simples e intuitiva, promovendo um atendimento diferenciado em todos os níveis.
Para maiores informações, acesse: https://p4f.com
Antes de instalar o plugin para WooCommerce, siga os passos abaixo para que sua experiência seja a mais tranquila, rápida e eficiente possível:
1. 	Envie um e-mail para merchant@p4f.com informando: nome/CNPJ da empresa que vai operar o e-commerce, nome/CPF do responsável, URL do e-commerce, principais produtos e serviços comercializados e dados da pessoa de contato (e-mail e telefone)
2.  	A Pay4Fun vai responder solicitando os documentos necessários ao processo de KYC (Conheça Seu Cliente) e proposta comercial.
3.  	Estando todos de acordo é só assinar o contrato. Daí você terá acesso ao Back Office onde disponibilizamos as credenciais necessárias para a instalação do plugin. Este Back Office também será o ambiente para acompanhar todas as suas transações, saldo, etc.
Importante:
1.  	O plugin não permite o estorno de transações (pay-out) ao cliente. O pay-out é possível via Back Office.
2.  	Se você já é um merchant da **Pay4Fun** através da API convencional, NÃO utilize as credenciais de tal API na instalação do plugin. Para utilizar o plugin para WooCommerce a partir da conta merchant existente, entre em contato pelo merchant@p4f.com solicitando a habilitação de sua conta merchant para o plugin.
3. Valor mínimo e máximo por transação é definido pelo merchant.
4. Aceitamos pagamentos em BRL, USD, EUR e GBP. A conta merchant é sempre em BRL.
5. Idiomas: português, inglês e espanhol.


= Requirements =

* A Pay4Fun Merchant’s account
* WordPress website with version 4.7 or higher
* WooCommerce Plugin (latest version)

* Uma conta Pay4Fun de merchant 
* Website WordPress versão 4.7 ou maior
* Plugin WooCommerce (última versão)

= Features =

* This plugin will add donations capabilities into your website, using a Pay4Fun account. You should be registered as a merchant in order to use this plugin.
* If you are using WooCommerce, it will add this payment option into your installation.

* Este plugin adiciona função de doação em seu website, utilizando uma conta Pay4Fun. Você deve estar registrado como merchant para poder usar esse plugin.
* Se você estiver utilizando o WooCommerce, adiciona esta opção de pagamento em sua instalação.



== Installation ==

* Before activating the plugin, make sure your WordPress is at least version 4.7, otherwise the plugin will not work. If using WooCommerce, install it before installing the Pay4Fun plugin.
* In the administrative panel of your WordPress site, navigate to Plugins> Add New and search for “Pay4Fun”.
* You can also install by directly downloading the plugin from the WordPress.org page and uploading the file at Plugins> Add New> Send Plugin.
* Navigate to Plugins> Installed plugins and activate the plugin to enable Pay4Fun options on your website.
* Navigate to Settings> Pay4fun and enter your merchant details (donation).
Navigate to WooCommerce> Settings> Payments> Pay4Fun and enter the merchant data (WordPress).


* Antes de ativar o plugin, garanta que seu WordPress está pelo menos na versão 4.7, caso contrário o plugin não irá funcionar. Se for usar o WooCommerce, realize a instalação do mesmo antes de instalar o plugin da Pay4Fun.
* No painel administrativo de seu site WordPress, navegue até a opção Plugins > Adicionar Novo e procure por “Pay4Fun”.
* Você também poderá instalar baixando diretamente o plugin na página da WordPress.org e realizando o upload do arquivo em Plugins > Adicionar Novo > Enviar Plugin.
* Navegue até Plugins > Plugins instalados e ative o plugin para habilitar as opções da Pay4Fun em seu site.
* Navegue até Configurações > Pay4fun e informe seus dados de merchant (doação).
Navegue até WooCommerce > Configurações > Pagamentos > Pay4Fun e insira os dados de merchant (WordPress).




== How to use this plugin ==

* The first step to start using this plugin is to register your merchant account with Pay4Fun. Follow the steps as informed in the “Description” section.

**DONATION**
* To enable the donation functions, within the WordPress administration panel, navigate through the side menu to Settings> Pay4Fun and enter the same merchant data (id, secret and key) that were created for you specifically for donations. IMPORTANT: do not use the same merchant id for donations and WooCommerce!
* You must also inform the currency and language that will be used to make the donations, in addition to defining the return page when the user is redirected back from the Pay4Fun checkout page. This page (success or failure), must be previously created by you before configuring this option. Use this option to direct the user to a page thanking the donation, for example.
* To add a donation button is very simple. There are 2 ways: via widget, which can be added in any location made available by the chosen theme, such as header, footer, sidebar, etc.), or via shortcode, which can be placed on any post or WordPress page, simply by placing the following code: [p4f-donate amount = 999] where the amount field indicates how much you want to donate, in the currency previously defined in the settings.
* You can check all donation records through the WordPress administration panel, navigating to Settings> Pay4Fun: Donations. Every time someone clicks on the donation button, the transaction will be recorded (successful or failed).

**WOOCOMMERCE**
* To use the payment functionality via WooCommerce, once it is installed and configured correctly, in the WordPress administration panel, navigate to WooCommerce> Settings> Payments> Pay4Fun, enable the payment method and configure the fields according to your need. Merchant fields are mandatory and must be filled in with the data provided.
* Remember that the plugin will use the same currency registered with WooCommerce, so make sure the store uses one of the supported currencies, otherwise it will not be possible to use this payment method, even if enabled.
* If you have more than one WooCommerce site for the same merchant ID, it is important to correctly register the order prefixes to ensure that the order numbers are uniquely identified within the Pay4Fun system.
* The order will be updated as confirmation of the transaction occurs by Pay4Fun. Once the transfer of values ​​is confirmed, the order will be updated and its status changed to “In Process” by default. If you want the order to be finalized as soon as the order is paid, select the corresponding option in the plugin settings.
* If for some reason there is no confirmation of payment (eg. withdrawal during checkout, insufficient balance, etc.), the order will be automatically canceled.


* O primeiro passo para começar a usar este plugin é registrar sua conta merchant com a Pay4Fun. Siga os passos conforme informado na seção “Descrição”.

**DOAÇÃO**
* Para habilitar as funções de doação, dentro do painel administrativo do WordPress, navegue pelo menu lateral até Configurações > Pay4Fun e coloque os mesmos dados de merchant (id, secret e key) que foram criados para você especificamente para as doações. IMPORTANTE: não utilize o mesmo id de merchant para doações e WooCommerce!
* Você também deverá informar a moeda e o idioma que será usada para realizar as doações, além de definir qual será a página de retorno quando o usuário for redirecionado de volta da página de checkout da Pay4Fun. Está página (de sucesso ou falha), deverá estar criada previamente por você antes de configurar esta opção. Utilize esta opção para direcionar o usuário para uma página agradecendo a doação, por exemplo.
* Para adicionar um botão de doação é muito simples. Há 2 formas: via widget, que pode ser adicionado em qualquer local disponibilizado pelo tema escolhido, como cabeçalho, footer, sidebar, etc.), ou via shortcode, que pode ser colocado em qualquer post ou página do WordPress, bastando colocar o seguinte código: [p4f-donate amount=999] onde o campo amount indica o quanto se deseja doar, na moeda previamente definida nas configurações.
* Você poderá verificar todos os registros de doação através do painel administrativo do WordPress, navegando até Configurações > Pay4Fun: Doações. Todas vez que alguém clicar no botão de doação, será registrado a transação (com sucesso ou falha).

**WOOCOMMERCE**
* Para usar a funcionalidade de pagamento via WooCommerce, uma vez que o mesmo esteja instalado e configurado corretamente, no painel administrativo do WordPress, navegar até WooCommerce > Configurações > Pagamentos > Pay4Fun, habilitar o método de pagamento e configurar os campos de acordo com sua necessidade. Os campos de merchant são mandatórios e devem ser preenchidos com os dados fornecidos.
* Vale lembrar que o plugin utilizará a mesma moeda registrada no WooCommerce, então certifique-se que a loja utilize uma das moedas suportadas, caso contrário não será possível usar este método de pagamento, mesmo que habilitado.
* Caso você possua mais de um site WooCommerce para um mesmo merchant ID, é importante cadastrar corretamente os prefixos para os pedidos, de forma a garantir que os números de pedidos sejam unicamente identificados dentro do sistema da Pay4Fun.
* O pedido será atualizado conforme a confirmação da transação ocorra pela Pay4Fun. Uma vez confirmada a transferência de valores, o pedido será atualizado e seu status modificado para “Em Processamento” por padrão. Caso queira que o pedido seja finalizado assim que o pedido estiver pago, selecione a opção correspondente nas configurações do plugin. 
* Se por alguma razão não houver confirmação do pagamento (ex. desistência durante o checkout, saldo insuficiente, etc), o pedido será cancelado automaticamente.



== Frequently Asked Questions ==

= I'm already a merchant at Pay4Fun, can I use the same ID I have? =

No, it is necessary to request activation of a specific merchant to use this plugin. Contact us at merchant@p4f.com requesting activation

= Why can't I use the same merchant id for donation and WooCommerce? =

Donations are exempt from commission, so they cannot be used for commercial purposes.

= I have more than one WooCommerce site, can I use the same merchant ID on all of them? =

Yes, but WooCommerce generates sequential order numbers, and so that there are no conflicts of these codes at Pay4Fun, it is important to correctly configure the order prefix to differentiate them between sites.

= Can I use only the donation feature? Do I need to install WooCommerce? =

It is not necessary to have WooCommerce installed if you do not have e-commerce. The plugin will work for donations independently. You only need to register a specific merchant with Pay4Fun to receive donations.


= Já sou merchant na Pay4Fun, posso usar o mesmo ID que tenho? =

Não, é necessário solicitar ativação de um merchant específico para usar este plugin. Entre em contato enviando um email para merchant@p4f.com solicitando a ativação

= Porque não posso usar o mesmo merchant id na doação e no WooCommerce? =

As doações são isentas de comissão, por este motivo não podem ser usadas para fins comerciais.

= Tenho mais de um site WooCommerce, posso usar o mesmo merchant ID em todos eles? =

Sim, porém o WooCommerce gera números de pedidos sequenciais, e para que não haja conflitos desses códigos na Pay4Fun, é importante configurar corretamente o prefixo de pedido de forma a diferenciá-los entre os sites.

= Posso usar somente a funcionalidade de doação? Preciso instalar o WooCommerce?  =

Não é necessário ter o WooCommerce instalado caso não tenha um e-commerce. O plugin funcionará para doações de forma independente. Somente é necessário registrar um merchant específico com a Pay4Fun para receber doações.


== Changelog ==

= 1.0 =
* Our first release. Includes a Donation Button and WooCommerce Integration.


== Upgrade Notice ==

= 1.0 =
* First Version.
