<template>
  <v-card flat>
    <v-card-title>
      <v-toolbar dense style="z-index: 1" color='tertiary'>
        <v-toolbar-title>
          <Breadcrumbs />
        </v-toolbar-title>
        <v-spacer></v-spacer>
        <template v-if="$route.params.workTray == 'received' || $route.params.workTray == 'my_received' || $route.params.workTray == 'validated'">
          <v-tooltip top>
            <template v-slot:activator="{ on }">
              <v-btn
                v-show="!loan.validated"
                v-on="on"
                icon
                outlined
                small
                color="success"
                class="darken-2 ml-4"
                @click="validation()"
              >
                <v-icon dark>mdi-file-check</v-icon>
              </v-btn>
            </template>
            <span>Validar trámite</span>
          </v-tooltip>
          <v-tooltip top>
            <template v-slot:activator="{ on }">
              <v-btn
                v-show="!loan.validated"
                v-on="on"
                icon
                outlined
                small
                color="orange"
                class="ml-4"
                @click="bus.$emit('openDialog', { edit: false, accion: 'devolver'})"
              >
                <v-icon>mdi-file-undo</v-icon>
              </v-btn>
            </template>
            <span>Devolver trámite</span>
          </v-tooltip>
          <v-tooltip top>
            <template v-slot:activator="{ on }">
              <v-btn
                top
                v-if="$store.getters.permissions.includes('delete-loan')"
                v-on="on"
                icon
                outlined
                small
                color="error"
                class="darken-2 ml-4"
                @click="
                  bus.$emit('openDialog', { edit: false, accion: 'anular' })
                "
              >
                <v-icon>mdi-file-cancel</v-icon>
              </v-btn>
            </template>
            <span>Anular trámite</span>
          </v-tooltip>
        </template>
        <template v-else><h6 class="caption">
          <v-icon x-small color="orange">mdi-folder-information</v-icon> {{role_name}} <br>
          <v-icon x-small color="blue" v-if="user_name != null">mdi-file-account</v-icon> {{user_name}}</h6>
        </template>
        <!--<v-divider
            class="mx-2"
            inset
            vertical
          ></v-divider>
          <v-flex xs3>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Buscar"
              class="mr-5 pr-5"
              single-line
              hide-details
              clearable
            ></v-text-field>
        </v-flex>-->
      </v-toolbar>
    </v-card-title>
    <v-card-text>
      <v-tabs
        v-model="tab"
        background-color="deep-blue accent-2"
        class="elevation-2"
        dark
        :vertical="vertical"
        :icons-and-text="icons"
      >
        <v-tabs-slider></v-tabs-slider>
        <v-tab v-if="!editable" :href="`#tab-1`">
          <v-tooltip bottom>
            <template v-slot:activator="{ on, attrs }">
              <v-icon v-if="icons" v-bind="attrs" v-on="on">mdi-trending-up</v-icon>
            </template>
            <span>
              <b>DATOS DEL PRESTAMO</b>
            </span>
          </v-tooltip>
        </v-tab>
        <v-tab :href="`#tab-2`">
          <v-tooltip bottom>
            <template v-slot:activator="{ on, attrs }">
              <v-icon v-if="icons" v-bind="attrs" v-on="on">mdi-file-eye</v-icon>
            </template>
            <span>
              <b>DATOS ESPECIFICOS DEL PRESTAMO</b>
            </span>
          </v-tooltip>
        </v-tab>
        <v-tab :href="`#tab-3`">
          <v-tooltip bottom>
            <template v-slot:activator="{ on, attrs }">
              <v-icon v-if="icons" v-bind="attrs" v-on="on">mdi-file</v-icon>
            </template>
            <span>
              <b>DOCUMENTOS PRESENTADOS</b>
            </span>
          </v-tooltip>
        </v-tab>
        <v-tab :href="`#tab-4`">
          <v-tooltip bottom>
            <template v-slot:activator="{ on, attrs }">
              <v-icon v-if="icons" v-bind="attrs" v-on="on">mdi-account</v-icon>
            </template>
            <span>
              <b>DATOS PERSONALES DEL AFILIADO</b>
            </span>
          </v-tooltip>
        </v-tab>
        <v-tab :href="`#tab-5`">
          <v-tooltip bottom>
            <template v-slot:activator="{ on, attrs }">
              <v-icon v-if="icons" v-bind="attrs" v-on="on">mdi-police-badge</v-icon>
            </template>
            <span>
              <b>INFORMACION POLICIAL</b>
            </span>
          </v-tooltip>
        </v-tab>
        <v-tab :href="`#tab-6`">
          <v-tooltip bottom>
            <template v-slot:activator="{ on, attrs }">
              <v-icon v-if="icons" v-bind="attrs" v-on="on">mdi-format-list-checkbox</v-icon>
            </template>
            <span>
              <b>KARDEX Y COBROS</b>
            </span>
          </v-tooltip>
        </v-tab>
        <v-tab :href="`#tab-7`">
          <v-tooltip bottom>
            <template v-slot:activator="{ on, attrs }">
              <v-icon v-if="icons" v-bind="attrs" v-on="on">mdi-comment-eye-outline</v-icon>
            </template>
            <span>
              <b>HISTORIAL DEL TRAMITE</b>
            </span>
          </v-tooltip>
        </v-tab>
        <v-tab-item :value="'tab-1'">
          <v-card flat tile>
            <v-card-text>
              <Dashboard :affiliate.sync="affiliate" :loan.sync="loan" :spouse.sync="spouse" />
            </v-card-text>
          </v-card>
        </v-tab-item>
        <v-tab-item :value="'tab-2'">
          <v-card flat tile>
            <v-card-title v-if="$store.getters.permissions.includes('print-payment-plan')">
              <v-tooltip top>
                <template v-slot:activator="{ on }">
                  <v-btn
                    fab
                    x-small
                    color="dark"
                    top
                    right
                    absolute
                    v-on="on"
                    style="margin-right: -9px; margin-top: 38px;"
                    @click="imprimir($route.params.id)"
                  >
                    <v-icon>mdi-printer</v-icon>
                  </v-btn>
                </template>
                <div>
                  <span>Imprimir plan de pagos</span>
                </div>
              </v-tooltip>
              <!--FORMULARIO PARA CALIFICACION-->
            </v-card-title>
            <v-card-title v-if="$store.getters.userRoles.includes('PRE-calificacion')">
              <v-tooltip top>
                <template v-slot:activator="{ on }">
                  <v-btn
                    fab
                    x-small
                    color="info"
                    top
                    right
                    absolute
                    v-on="on"
                    style="margin-right: -9px;margin-top: 78px;"
                    @click="calificacionprint($route.params.id)"
                  >
                    <v-icon>mdi-printer-check</v-icon>
                  </v-btn>
                </template>
                <div>
                  <span>Imprimir Formulario de calificacion</span>
                </div>
              </v-tooltip>
            </v-card-title>
            <v-card-text class="pl-10">
              <SpecificDataLoan
                :loan.sync="loan"
                :loan_properties="loan_properties"
                :procedure_types="procedure_types"
                
              >
                <template v-slot:title>
                  <v-col cols="12" class="py-0">
                    <v-toolbar-title>
                      <b>DATOS ESPECIFICOS DEL PRÉSTAMO</b>
                    </v-toolbar-title>
                  </v-col>
                </template>
              </SpecificDataLoan>
            </v-card-text>
          </v-card>
        </v-tab-item>
        <v-tab-item :value="'tab-3'">
          <v-card flat tile>
            <v-card-text class="pl-12">
              <DocumentsFlow>
                <template v-slot:title>
                  <v-col cols="12" class>
                    <v-toolbar-title>DOCUMENTOS PRESENTADOS</v-toolbar-title>
                  </v-col>
                </template>
              </DocumentsFlow>
            </v-card-text>
          </v-card>
        </v-tab-item>
        <v-tab-item :value="'tab-4'">
          <v-card flat tile>
            <v-card-text class="pl-0 py-0">
              <Profile
                v-if="!reload"
                :affiliate.sync="affiliate"
                :addresses.sync="addresses"
                :editable.sync="editable"
                :permission="permission"
              />
            </v-card-text>
          </v-card>
        </v-tab-item>
        <v-tab-item :value="'tab-5'">
          <v-card flat tile>
            <v-card-text class="py-0 pl-0">
              <PoliceData
                v-if="!reload"
                :affiliate.sync="affiliate"
                :editable.sync="editable"
                :permission="permission"
              />
            </v-card-text>
          </v-card>
        </v-tab-item>

        <v-tab-item :value="'tab-6'">
          <v-card flat tile>
            <v-card-text class="pa-0 pl-3 pr-0 py-0">
              <Kardex :bus="bus" />
            </v-card-text>
          </v-card>
        </v-tab-item>

        <v-tab-item :value="'tab-7'">
          <v-card flat tile>
            <v-card-text class="pa-0 pl-3 pr-0 py-0">
              <ObserverFlow :loan.sync="loan" :observations.sync="observations" :bus1="bus1" />
            </v-card-text>
          </v-card>
        </v-tab-item>
      </v-tabs>
    </v-card-text>
    <AddObservation :bus="bus" :loan="loan" />
  </v-card>
</template>
<script>
import Breadcrumbs from "@/components/shared/Breadcrumbs"
import Profile from "@/components/affiliate/Profile"
import SpecificDataLoan from "@/components/loan/SpecificDataLoan"
import DocumentsFlow from "@/components/workflow/DocumentsFlow"
import ObserverFlow from "@/components/workflow/ObserverFlow"
import AddObservation from "@/components/workflow/AddObservation"
import PoliceData from "@/components/affiliate/PoliceData"
import Dashboard from "@/components/workflow/Dashboard"
import Kardex from "@/components/payment/Kardex"

export default {
  name: "flow-index",
  components: {
    Breadcrumbs,
    Profile,
    SpecificDataLoan,
    DocumentsFlow,
    PoliceData,
    ObserverFlow,
    AddObservation,
    Dashboard,
    Kardex
  },
  data: () => ({
    bus: new Vue(),
    search: "",
    bus1: new Vue(), //Creamos la instancia de bus1
    addresses: [],
    affiliate: {
      first_name: null,
      second_name: null,
      last_name: null,
      mothers_last_name: null,
      identity_card: null,
      birth_date: null,
      date_death: null,
      reason_death: null,
      phone_number: null,
      cell_phone_number: null,
      city_identity_card_id: null,
      date_entry: null,
      service_years: null,
      service_months: null,
      date_derelict: null,
      unit_name: null
    },
    bonos: [0, 0, 0, 0],
    payable_liquid: [0, 0, 0],
    modalidad: {},
    loan: {},
    datos: {},
    formulario: [],
    observations: [],
    spouse: {},
    loan_properties: {},
    procedure_types: {},
    intervalos: {},
    modalidad: {},
    icons: true,
    vertical: true,
    editable: false,
    reload: false,
    tab: "tab-1",
    validate: {
      valid_disbursement: false
    },
    role_name: null,
    user_name: null
  }),
  watch: {
    search: _.debounce(function() {
      this.bus.$emit("search", this.search)
    }, 1000)
  },
  computed: {
    permission() {
      return {
        primary: this.primaryPermission,
        secondary: this.secondaryPermission
      }
    },
    secondaryPermission() {
      if (this.affiliate.id) {
        return this.$store.getters.permissions.includes(
          "update-affiliate-secondary"
        )
      } else {
        return this.$store.getters.permissions.includes("create-affiliate")
      }
    },
    primaryPermission() {
      if (this.affiliate.id) {
        return this.$store.getters.permissions.includes(
          "update-affiliate-primary"
        )
      } else {
        return this.$store.getters.permissions.includes("create-affiliate")
      }
    }
  },
  mounted() {
    this.getloan(this.$route.params.id)
    this.getSpouse(this.$route.params.id)
    this.getObservation(this.$route.params.id)
    this.getProceduretype(this.$route.params.id)
    this.bus1.$on("emitGetObservation", id => {
      //escuchamos la emision de ObserverFlow
      this.getObservation(id) //y monstramos la lista de observaciones segun el id del prestamo
    })
  },
  methods: {
    resetForm() {
      this.getAddress(this.$route.params.id)
      this.editable = false
      this.reload = true
      this.$nextTick(() => {
        this.reload = false
      })
    },
    setBreadcrumbs() {
      let breadcrumbs = [
        {
          text: "Préstamo",
          to: { name: "flowIndex" }
        }
      ]
      breadcrumbs.push({
        text: this.loan.code,
        to: { name: "flowAdd", params: { id: this.loan.id } }
      })
      this.$store.commit("setBreadcrumbs", breadcrumbs)
    },
    async getloan(id) {
      try {
        this.loading = true
        let res = await axios.get(`loan/${id}`)
        this.loan = res.data
        console.log("este es el loan" + this.loan)
        let res1 = await axios.get(`affiliate/${this.loan.lenders[0].id}`)
        this.affiliate = res1.data
        if (this.loan.property_id != null) {
          this.getLoanproperty(this.loan.property_id)
        }
        this.getProceduretype(this.loan.procedure_modality_id)
        if (this.loan.disbursable_type == "spouses") {
          this.getSpouse(this.affiliate.id)
        }
        this.setBreadcrumbs()
        this.role(this.loan.role_id)
        this.user(this.loan.user_id)
        console.log(this.loan)
      } catch (e) {
        console.log(e)
      } finally {
        this.loading = false
      }
    },
    async getSpouse(id) {
      try {
        this.loading = true
        let res = await axios.get(`affiliate/${id}/spouse`)
        this.spouse = res.data
      } catch (e) {
        console.log(e)
      } finally {
        this.loading = false
      }
    },

    async getLoanproperty(id) {
      try {
        this.loading = true
        let res = await axios.get(`loan_property/${id}`)
        this.loan_properties = res.data
      } catch (e) {
        console.log(e)
      } finally {
        this.loading = false
      }
    },

    async getProceduretype(id) {
      try {
        this.loading = true
        let res = await axios.get(`procedure_modality/${id}`)
        this.procedure_types = res.data
      } catch (e) {
        console.log(e)
      } finally {
        this.loading = false
      }
    },

    async getObservation(id) {
      try {
        this.loading = true
        let res = await axios.get(`loan/${id}/observation`)
        this.observations = res.data
        for (this.i = 0; this.i < this.observations.length; this.i++) {
          let res1 = await axios.get(`user/${this.observations[this.i].user_id}`
          )
          this.observations[this.i].user_name = res1.data.username
        }
      } catch (e) {
        console.log(e)
      } finally {
        this.loading = false
      }
    },
    async getAddress(id) {
      try {
        this.loading = true
        let res = await axios.get(`affiliate/${id}/address`)
        this.addresses = res.data
      } catch (e) {
        console.log(e)
      } finally {
        this.loading = false
      }
    },
    async imprimir(item) {
      try {
        let res = await axios.get(`loan/${item}/print/plan`)
        console.log("plan " + item)
        printJS({
          printable: res.data.content,
          type: res.data.type,
          file_name: res.data.file_name,
          base64: true
        })
      } catch (e) {
        this.toastr.error("Ocurrió un error en la impresión.")
        console.log(e)
      }
    },
    async calificacionprint(item) {
      try {
        let res = await axios.get(`loan/${item}/print/qualification`)
        console.log("plan " + item)
        printJS({
          printable: res.data.content,
          type: res.data.type,
          file_name: res.data.file_name,
          base64: true
        })
      } catch (e) {
        this.toastr.error("Ocurrió un error en la impresión.")
        console.log(e)
      }
    },
    async role(role_id){
      try {
        let res = await axios.get(`role/${role_id}`)
        this.role_name = res.data.display_name
        console.log(this.role_name)
      } catch (e) {
        console.log(e)
      }
    },
    async user(user_id){
      try {
        let res = await axios.get(`user/${user_id}`)
        this.user_name = res.data.username
        console.log(this.user_name)
      } catch (e) {
        console.log(e)
      }
    },
    validation(){
      //VALIDACION DESEMBOLSO
      if((this.loan.disbursement_date != '' && this.loan.number_payment_type != '') && (this.loan.disbursement_date != null && this.loan.number_payment_type != null)){
        this.validate.valid_disbursement = true
      }else{
        this.validate.valid_disbursement = false
      }
      /////
      if(this.$store.getters.permissions.includes('disbursement-loan') == true && this.validate.valid_disbursement == true){
         this.bus.$emit('openDialog', { edit: false, accion: 'validar' })
         //alert("entro")
      }
      else if(this.$store.getters.permissions.includes('disbursement-loan') == false){
         this.bus.$emit('openDialog', { edit: false, accion: 'validar' })
         //alert("entro 2")
      }
      else{
        this.toastr.error('Faltan registar campos en Desembolso. Registre la fecha, tipo y nro de documento.')
      }

    }
  }
}
</script>