AppenderSMTPMailEvent para apache log4php
=====================

  Envie logs atráves de SMTP autenticado


Configuração
====================

  Adicione a classe LoggerAppenderSMTPMailEvent.php ao seu diretório /log4php/appenders/
  
  Em seguida crie/edite o seu arquivo de configuração de log com os seguintes parametros

  &lt;appender name="exportAllNFNREmail" class="LoggerAppenderSMTPMailEvent"&gt;
    	&lt;layout class='XXX'&gt;
    	&lt;/layout&gt;
	&lt;param name="to" value="destinatario@test.com.br" /&gt;
    	&lt;param name="smtpHost" value="smtp.test.com.br"/&gt;
    	&lt;param name="from" value="remetente@test.com.br"/&gt;
    	&lt;param name='username' value='remetente@test.com.br' /&gt;
    	&lt;param name='password' value='your_password' /&gt;
	&lt;param name="subject" value="Email title" /&gt;
  &lt;/appender&gt;
