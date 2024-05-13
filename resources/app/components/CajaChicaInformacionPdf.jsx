import { Page, Text, View, Document, StyleSheet, Image, PDFViewer, Font } from '@react-pdf/renderer';
import { createTw } from 'react-pdf-tailwind'
import { convertirFecha, convertirFechaSinHora, convertirNumeroALetras } from '../helpers/CajaChica';
import useProyect from '../hooks/useProyect';

const tw = createTw({
    theme: {
        fontFamily: {
            sans: ["Comic Sans"],
        },
        extend: {
            colors: {
                custom: "#bada55",
            },
        },
    },
});

export default function CajaChicaInformationPdf() {

    const { gastoElegido } = useProyect();
    const { nroFactura, nro, gasto: costo, id, descripcion, created_at, interested, manager } = gastoElegido;
    const { nombres, apellidoPaterno, apellidoMaterno } = manager
    // console.log(gastoElegido)
    return (
        <PDFViewer style={styles.container}>
            <Document>
                <Page style={styles.body} wrap>

                    <>
                        {/* COMPROBANTE DE CAJA CHICA */}
                        <View style={styles.header}>
                            <Image
                                src="/images/Logo UMSA.png"
                                style={styles.imageLogo}
                            />
                            <View style={styles.titleContainer}>
                                <Text style={styles.title}>UNIVERSIDAD MAYOR DE SAN ANDRÉS</Text>
                                <Text style={styles.title}>FACULTAD DE MEDICINA, ENFERMERÍA, NUTRICIÓN Y TECNOLOGÍA MÉDICA </Text>
                                <Text style={styles.title}>CARRERA DE TECNOLOGÍA MÉDICA</Text>
                            </View>
                            <Image
                                src="/images/logoTecMed.png"
                                style={styles.imageLogoCarrera}
                            />
                        </View>
                        <View style={styles.titleNumber}>
                            <Text style={styles.titleWhite}>adasdsadsds12312</Text>
                            <Text style={styles.titleBigger}>COMPROBANTE DE CAJA CHICA</Text>
                            <Text style={styles.titleBigger}>N       {nro}</Text>
                        </View>
                        <View style={styles.content}>
                            <View style={styles.divSeparador}>
                                <Text style={tw("text-red-500")}>Ciudad: <Text style={styles.span}>La Paz</Text></Text>
                                <Text style={tw("text-red-500")}>Fecha: <Text style={styles.span}>{convertirFechaSinHora(created_at)}</Text></Text>
                            </View>
                            <View style={styles.divSeparador}>
                                <Text style={tw("text-red-500")}>Pagado a: <Text style={styles.span}>{interested}</Text></Text>
                                {
                                    !nroFactura && (<Text style={tw("text-red-500")}>Nro. de Vale: <Text style={styles.span}>{nro}</Text></Text>)
                                }
                                {
                                    nroFactura && <Text style={tw("text-red-500")}>Nro. de Factura: <Text style={styles.span}>{nroFactura}</Text></Text>
                                }

                            </View>
                            <View style={styles.divSeparador}>
                                <Text style={tw("text-red-500")}>La suma de:<Text style={styles.span}> {costo} Bs.</Text></Text>
                                <Text style={tw("text-red-500")}>Literal: <Text style={styles.span}>{convertirNumeroALetras(costo)} en efectivo</Text></Text>
                            </View>
                            <Text style={tw("text-red-500 text-justify leading-6")}>Por concepto de: <Text style={styles.span}>{descripcion}</Text></Text>
                            <View style={styles.firmasContent}>
                                <Text>Firma autorizada</Text>
                                <View style={styles.firmas}>
                                    {/* <Text>María del Carmen Murillo de Espinoza</Text> */}
                                    <Text>{`${nombres} ${apellidoPaterno} ${apellidoMaterno}`}</Text>
                                    <Text>RESPONSABLE</Text>
                                    <Text>ENTREGUE CONFORME</Text>
                                </View>
                                {
                                    !nroFactura && <View style={styles.firmas}>
                                        <Text>{interested}</Text>
                                        <Text>INTERESADO</Text>
                                        <Text>RECIBI CONFORME</Text>
                                    </View>
                                }

                            </View>
                        </View>

                    </>

                    {/* COMPROBANTE DE VALE */}
                    {
                        !nroFactura && (
                            <>
                                <View style={styles.valeMovilidadContainer}>
                                    <View style={styles.header}>
                                        <Image
                                            src="/images/Logo UMSA.png"
                                            style={styles.imageLogo}
                                        />
                                        <View style={styles.titleContainer}>
                                            <Text style={styles.title}>UNIVERSIDAD MAYOR DE SAN ANDRÉS</Text>
                                            <Text style={styles.title}>FACULTAD DE MEDICINA, ENFERMERÍA, NUTRICIÓN Y TECNOLOGÍA MÉDICA </Text>
                                            <Text style={styles.title}>CARRERA DE TECNOLOGÍA MÉDICA</Text>
                                        </View>
                                        <Image
                                            src="/images/logoTecMed.png"
                                            style={styles.imageLogoCarrera}
                                        />
                                    </View>
                                    <View style={styles.titleNumber}>
                                        <Text style={styles.titleWhite}>adasdsadsds12312</Text>
                                        <Text style={styles.titleBigger}>VALE DE MOVILIDAD</Text>
                                        <Text style={styles.titleBigger}>N       {nro}</Text>
                                    </View>

                                    <View style={styles.content}>
                                        <View style={styles.divSeparador}>
                                            <Text style={tw("text-red-500")}>Ciudad: <Text style={styles.span}>La Paz</Text></Text>
                                            <Text style={tw("text-red-500")}>Fecha: <Text style={styles.span}>{convertirFechaSinHora(created_at)}</Text></Text>
                                        </View>
                                        <View style={styles.divSeparador}>
                                            <Text style={tw("text-red-500")}>Pagado a: <Text style={styles.span}>{interested}</Text></Text>
                                            <Text style={tw("text-red-500")}>Nro. de Vale: <Text style={styles.span}>{nro}</Text></Text>
                                            {
                                                nroFactura && <Text style={tw("text-red-500")}>Nro. de Factura: <Text style={styles.span}>{nroFactura}</Text></Text>
                                            }

                                        </View>
                                        <View style={styles.divSeparador}>
                                            <Text style={tw("text-red-500")}>La suma de:<Text style={styles.span}>{costo} Bs.</Text></Text>
                                            <Text style={tw("text-red-500")}>Literal: <Text style={styles.span}>{convertirNumeroALetras(costo)} en efectivo</Text></Text>
                                        </View>
                                        <Text style={tw("text-red-500 text-justify leading-6")}>Por concepto de: <Text style={styles.span}>{descripcion}</Text></Text>
                                        <View style={styles.firmasContent}>
                                            <Text>Firma autorizada</Text>
                                            <View style={styles.firmas}>
                                                {/* <Text>María del Carmen Murillo de Espinoza</Text> */}
                                                <Text>{`${nombres} ${apellidoPaterno} ${apellidoMaterno}`}</Text>
                                                <Text>RESPONSABLE</Text>
                                                <Text>ENTREGUE CONFORME</Text>
                                            </View>
                                            <View style={styles.firmas}>
                                                <Text>{interested}</Text>
                                                <Text>INTERESADO</Text>
                                                <Text>RECIBI CONFORME</Text>
                                            </View>
                                        </View>
                                    </View>
                                </View>
                            </>
                        )
                    }
                </Page>
            </Document>
        </PDFViewer>
    )
}

const styles = StyleSheet.create({
    container: {
        width: '100%',
        height: '100%',
        fontFamily: 'OpenSans'
    },
    titleContainer: {
        display: 'flex',
        alignItems: 'center',
        fontSize: '10px',
    },
    valeMovilidadContainer: {
        marginTop: '30px'
    },
    body: {
        padding: '20px',
        fontSize: '11px'
    },
    header: {
        display: 'flex',
        flexDirection: 'row',
        justifyContent: 'space-around',
        alignItems: 'center',
    },
    imageLogo: {
        width: '43px',
        height: '80px',
        overflow: 'hidden'
    },
    imageLogoCarrera: {
        width: '50px',
        height: '70px',
    },
    title: {
        textAlign: 'center',
        marginBottom: '5px',
    },
    titleBigger: {
        fontSize: '20px',
        fontWeight: 'bold',
        marginBottom: '5px'
    },
    content: {
        display: 'flex',
        gap: '10',
        paddingHorizontal: '13px',
        marginTop: '15px',
    },
    divSeparador: {
        display: 'flex',
        flexDirection: 'row',
        justifyContent: 'space-between',
    },
    span: {
        color: 'black'
    },
    firmasContent: {
        display: 'flex',
        flexDirection: 'row',
        justifyContent: 'space-around',
        alignItems: 'center',
        marginTop: '35px'
    },
    firmas: {
        display: 'flex',
        alignItems: 'center',
        lineHeight: '1.5'
    },
    titleNumber: {
        display: 'flex',
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginRight: '20px'
    },
    titleWhite: {
        color: 'white'
    }


})