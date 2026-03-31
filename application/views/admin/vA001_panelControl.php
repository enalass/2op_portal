<?PHP
  if(($this->session->userdata('logged'))==TRUE) {
    if(($this->session->userdata('acceso'))>=100){
    $dashboard = isset($dashboard) && is_array($dashboard) ? $dashboard : array();
    $kpis = isset($dashboard['kpis']) && is_array($dashboard['kpis']) ? $dashboard['kpis'] : array();
    $pagos = isset($dashboard['pagos']) && is_array($dashboard['pagos']) ? $dashboard['pagos'] : array();
    $estados = isset($dashboard['estados']) && is_array($dashboard['estados']) ? $dashboard['estados'] : array();
    $evolucion = isset($dashboard['evolucion']) && is_array($dashboard['evolucion']) ? $dashboard['evolucion'] : array('labels'=>array(), 'solicitudes'=>array(), 'ingresos'=>array());
    $supuestos = isset($dashboard['supuestos']) && is_array($dashboard['supuestos']) ? $dashboard['supuestos'] : array();

    $fmtNumber = function($value){
      return number_format((float)$value, 0, ',', '.');
    };
    $fmtDecimal = function($value, $digits = 2){
      return number_format((float)$value, (int)$digits, ',', '.');
    };
    $tiempoMedio = isset($kpis['tiempo_medio_lead_a_estado2_horas']) ? $kpis['tiempo_medio_lead_a_estado2_horas'] : null;
    $tiempoMedioEstado2a3 = isset($kpis['tiempo_medio_estado2_a_estado3_horas']) ? $kpis['tiempo_medio_estado2_a_estado3_horas'] : null;
    $tiempoMedioLeadPagado = isset($kpis['tiempo_medio_lead_a_pagado_horas']) ? $kpis['tiempo_medio_lead_a_pagado_horas'] : null;
?>
  <div class="row">
    <div class="col-xl-3 col-md-6">
      <div class="card card-custom gutter-b">
        <div class="card-body">
          <span class="svg-icon svg-icon-2x svg-icon-primary mb-2">
            <i class="flaticon2-list-1 text-primary"></i>
          </span>
            <div class="text-dark font-weight-bolder font-size-h2 mt-2"><?php echo $fmtNumber(isset($kpis['leads_pendientes']) ? $kpis['leads_pendientes'] : 0); ?></div>
            <div class="text-muted font-weight-bold">Leeds pendientes</div>
            <div class="mt-3">
              <a href="<?php echo site_url('admin/cA003_solicitudes?estado=1'); ?>" class="btn btn-sm btn-light-primary">Ir a solicitudes</a>
            </div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="card card-custom gutter-b">
        <div class="card-body">
          <span class="svg-icon svg-icon-2x svg-icon-success mb-2">
            <i class="flaticon-calendar-with-a-clock-time-tools text-success"></i>
          </span>
          <div class="text-dark font-weight-bolder font-size-h2 mt-2"><?php echo $fmtNumber(isset($kpis['solicitudes_mes_actual']) ? $kpis['solicitudes_mes_actual'] : 0); ?></div>
          <div class="text-muted font-weight-bold">Solicitudes este mes</div>
          <div class="mt-3">
            <a href="<?php echo site_url('admin/cA003_solicitudes'); ?>" class="btn btn-sm btn-light-success">Ver solicitudes</a>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="card card-custom gutter-b">
        <div class="card-body">
          <span class="svg-icon svg-icon-2x svg-icon-warning mb-2">
            <i class="flaticon-time text-warning"></i>
          </span>
          <div class="text-dark font-weight-bolder font-size-h2 mt-2"><?php echo $fmtNumber(isset($kpis['solicitudes_estado_2']) ? $kpis['solicitudes_estado_2'] : 0); ?></div>
            <div class="text-muted font-weight-bold">Esperando pago</div>
            <div class="mt-3">
              <a href="<?php echo site_url('admin/cA003_solicitudes?estado=2'); ?>" class="btn btn-sm btn-light-warning">Ver solicitudes</a>
            </div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="card card-custom gutter-b">
        <div class="card-body">
          <span class="svg-icon svg-icon-2x svg-icon-danger mb-2">
            <i class="flaticon2-analytics text-danger"></i>
          </span>
          <div class="text-dark font-weight-bolder font-size-h2 mt-2"><?php echo $fmtDecimal(isset($kpis['tasa_conversion_pagada']) ? $kpis['tasa_conversion_pagada'] : 0); ?>%</div>
          <div class="text-muted font-weight-bold">Conversion a pagada</div>
          <div class="mt-3">
            <a href="<?php echo site_url('admin/cA005_pagos'); ?>" class="btn btn-sm btn-light-danger">Ir a pagos</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-xl-6">
      <div class="card card-custom gutter-b">
        <div class="card-header border-0 pt-5">
          <h3 class="card-title font-weight-bolder">Indicadores de negocio</h3>
        </div>
        <div class="card-body pt-2">
          <div class="d-flex justify-content-between mb-4">
              <span class="text-muted">Tiempo medio lead -> solicitado pago</span>
            <span class="font-weight-bold text-dark"><?php echo $tiempoMedio !== null ? $fmtDecimal($tiempoMedio) . ' h' : 'N/D'; ?></span>
          </div>
            <div class="d-flex justify-content-between mb-4">
              <span class="text-muted">Tiempo medio solicitado pago -> pagado</span>
              <span class="font-weight-bold text-dark"><?php echo $tiempoMedioEstado2a3 !== null ? $fmtDecimal($tiempoMedioEstado2a3) . ' h' : 'N/D'; ?></span>
            </div>
          <div class="d-flex justify-content-between mb-4">
            <span class="text-muted">Ingreso mensual medio</span>
            <span class="font-weight-bold text-dark"><?php echo $fmtDecimal(isset($kpis['ingreso_mensual_medio']) ? $kpis['ingreso_mensual_medio'] : 0); ?> EUR</span>
          </div>
          <div class="d-flex justify-content-between mb-4">
            <span class="text-muted">Solicitudes pagadas</span>
            <span class="font-weight-bold text-dark"><?php echo $fmtNumber(isset($kpis['solicitudes_pagadas']) ? $kpis['solicitudes_pagadas'] : 0); ?></span>
          </div>
          <div class="d-flex justify-content-between mb-4">
            <span class="text-muted">Tiempo medio lead -> pagado</span>
            <span class="font-weight-bold text-dark"><?php echo $tiempoMedioLeadPagado !== null ? $fmtDecimal($tiempoMedioLeadPagado) . ' h' : 'N/D'; ?></span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-6">
      <div class="card card-custom gutter-b">
        <div class="card-header border-0 pt-5">
          <h3 class="card-title font-weight-bolder">Distribucion por estado</h3>
        </div>
        <div class="card-body pt-2">
          <?php if(empty($estados)): ?>
            <div class="alert alert-light mb-0">No hay datos de estados para mostrar.</div>
          <?php else: ?>
            <?php foreach($estados as $estado): ?>
              <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <span class="text-dark font-weight-bold"><?php echo html_escape($estado['estado_nombre']); ?></span>
                  <span class="text-muted"><?php echo $fmtNumber($estado['total']); ?></span>
                </div>
                <div class="progress" style="height: 10px;">
                  <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo (float)$estado['percent']; ?>%;" aria-valuenow="<?php echo (float)$estado['percent']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-xl-12">
      <div class="card card-custom gutter-b">
        <div class="card-header border-0 pt-5">
          <h3 class="card-title font-weight-bolder">Evolucion mensual (ultimos 6 meses)</h3>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-head-custom table-vertical-center">
              <thead>
                <tr>
                  <th>Mes</th>
                  <th>Solicitudes</th>
                  <th>Ingresos (EUR)</th>
                </tr>
              </thead>
              <tbody>
              <?php
                $labels = isset($evolucion['labels']) ? $evolucion['labels'] : array();
                $serieSol = isset($evolucion['solicitudes']) ? $evolucion['solicitudes'] : array();
                $serieIng = isset($evolucion['ingresos']) ? $evolucion['ingresos'] : array();
                for($i = 0; $i < count($labels); $i++):
              ?>
                <tr>
                  <td><?php echo html_escape($labels[$i]); ?></td>
                  <td><?php echo $fmtNumber(isset($serieSol[$i]) ? $serieSol[$i] : 0); ?></td>
                  <td><?php echo $fmtDecimal(isset($serieIng[$i]) ? $serieIng[$i] : 0); ?></td>
                </tr>
              <?php endfor; ?>
              </tbody>
            </table>
          </div>

          <canvas id="dashboardMiniChart" height="120"></canvas>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function(){
      var canvas = document.getElementById('dashboardMiniChart');
      if(!canvas){
        return;
      }
      var ctx = canvas.getContext('2d');
      if(!ctx){
        return;
      }

      var labels = <?php echo json_encode(isset($evolucion['labels']) ? $evolucion['labels'] : array()); ?>;
      var solicitudes = <?php echo json_encode(isset($evolucion['solicitudes']) ? $evolucion['solicitudes'] : array()); ?>;
      var ingresos = <?php echo json_encode(isset($evolucion['ingresos']) ? $evolucion['ingresos'] : array()); ?>;

      var width = canvas.width;
      var height = canvas.height;
      ctx.clearRect(0, 0, width, height);

      if(!labels.length){
        ctx.fillStyle = '#7e8299';
        ctx.font = '14px Arial';
        ctx.fillText('No hay datos para grafico', 10, 24);
        return;
      }

      var maxSolicitudes = Math.max.apply(Math, solicitudes.concat([1]));
      var maxIngresos = Math.max.apply(Math, ingresos.concat([1]));
      var maxValue = Math.max(maxSolicitudes, maxIngresos, 1);

      var padding = 30;
      var stepX = (width - padding * 2) / Math.max(labels.length - 1, 1);

      ctx.strokeStyle = '#e4e6ef';
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.moveTo(padding, height - padding);
      ctx.lineTo(width - padding, height - padding);
      ctx.stroke();

      function drawSeries(series, color){
        ctx.strokeStyle = color;
        ctx.fillStyle = color;
        ctx.lineWidth = 2;
        ctx.beginPath();
        for(var i = 0; i < series.length; i++){
          var x = padding + (stepX * i);
          var y = (height - padding) - ((series[i] / maxValue) * (height - (padding * 2)));
          if(i === 0){
            ctx.moveTo(x, y);
          } else {
            ctx.lineTo(x, y);
          }
        }
        ctx.stroke();

        for(var j = 0; j < series.length; j++){
          var px = padding + (stepX * j);
          var py = (height - padding) - ((series[j] / maxValue) * (height - (padding * 2)));
          ctx.beginPath();
          ctx.arc(px, py, 3, 0, Math.PI * 2);
          ctx.fill();
        }
      }

      drawSeries(solicitudes, '#3699ff');
      drawSeries(ingresos, '#1bc5bd');

      ctx.fillStyle = '#7e8299';
      ctx.font = '11px Arial';
      for(var k = 0; k < labels.length; k++){
        var lx = padding + (stepX * k);
        ctx.fillText(labels[k].substr(2), lx - 14, height - 10);
      }

      ctx.fillStyle = '#3699ff';
      ctx.fillRect(width - 190, 12, 10, 10);
      ctx.fillStyle = '#7e8299';
      ctx.fillText('Solicitudes', width - 175, 22);

      ctx.fillStyle = '#1bc5bd';
      ctx.fillRect(width - 100, 12, 10, 10);
      ctx.fillStyle = '#7e8299';
      ctx.fillText('Ingresos', width - 85, 22);
    })();
  </script>
<?PHP
    }else{
      ?>
        <section>
          <h2>ACCESS DENIED, CONTACT YOUR ADMINISTRATOR</h2>
        </section>
      <?PHP
    }
  }
?>