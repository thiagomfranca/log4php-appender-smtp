AppenderSMTPMailEvent para apache log4php
=====================

  Envie logs atráves de SMTP autenticado


Configuração
====================

  Adicione a classe LoggerAppenderSMTPMailEvent.php ao seu diretório /log4php/appenders/
  
  Em seguida crie/edite o seu arquivo de configuração de log com os seguintes parametros

  	<appender name="exportAllNFNREmail" class="LoggerAppenderSMTPMailEvent">
		<param name="to" value="destinatario@test.com.br" />
		<param name="smtpHost" value="smtp.test.com.br"/>
		<param name="from" value="remetente@test.com.br"/>
		<param name='username' value='remetente@test.com.br' />
		<param name='password' value='your_password' />
		<param name="subject" value="Email title" />
  	</appender>
